<?php

/**
 * 我的钱包
 *
 * @author JiangJian & UncleZ <silverd@sohu.com>
 */

class Model_User_Wallet extends Model_User_Trait
{
    // 单次最低允许提现金额（分）
    const MIN_WITHDRAW_MONEY = 100;

    // 每日提现次数上限
    const MAX_DAILY_WITHDRAW_TIMES = 99;

    const TYPE_IN  = 1; // 收入
    const TYPE_OUT = 2; // 支出

    // 钱包使用场景
    public static $ACTIONS = [

        // 加
        50098 => '退还提现申请',
        50099 => '给账户充值',
        50100 => '退还服务费',

        50101 => '服务费分成',       // 预订成功后，当订单取消或入住后，助理可以得到服务费分成

        // 减
        50801 => '支付服务费',
        50803 => '提现申请',         // 提现申请，扣除可用余额
    ];

    /**
     * 增加账户余额
     *
     * @param int $amount 消耗金额（分）
     * @param int $action
     * @param string $memo 备注
     * @return bool
     */
    public function deposit($amount, $action, $memos = '', $moneyType = 'gift')
    {
        if ($amount < 1) {
            return false;
        }

        if (! isset(self::$ACTIONS[$action])) {
            return false;
        }

        // 增加金额
        $this->_user->update(['money'=> ['+', $amount]]);

        // 记录收支明细日志
        Dao('Massive_LogUserMoney')->insert([
            'uid'        => $this->_uid,
            'type'       => self::TYPE_IN,
            'action'     => $action,
            'money'      => $amount,
            'memos'      => $memos,
            'created_at' => $GLOBALS['_DATE'],
        ]);

        // 增加“充值余额”
        if ($moneyType == 'rmb') {
            // 用户数据统计
            $this->_user->stats->incr('money_rmb_total_in', $amount);
            // 每小时行为统计
            $this->_user->hourlyStats->incr('money_rmb_incr', $amount);
        }
        // 增加“赠送余额”
        else {
            // 用户数据统计
            $this->_user->stats->incr('money_gift_total_in', $amount);
            // 每小时行为统计
            $this->_user->hourlyStats->incr('money_gift_incr', $amount);
        }

        // 余额变更-模板消息通知
        Model_Weixin_TplMsg_Biz::walletMoneyChanged($this->_uid, $amount, $this->_user['money']);

        return true;
    }

    /**
     * 消耗账户余额
     *
     * @param int $amount 消耗金额（分）
     * @param int $action
     * @param string $memo 备注
     * @return bool
     */
    public function consume($amount, $action, $memos = '')
    {
        if ($amount < 1) {
            return false;
        }

        if (! isset(self::$ACTIONS[$action])) {
            return false;
        }

        // 执行扣除
        $this->_user->update(['money'=> ['-', $amount]]);

        // 增加消费日志
        Dao('Massive_LogUserMoney')->insert([
            'uid'        => $this->_uid,
            'type'       => self::TYPE_OUT,
            'action'     => $action,
            'money'      => $amount,
            'memos'      => $memos,
            'created_at' => $GLOBALS['_DATE'],
        ]);

         // 增加用户数据统计
        $this->_user->stats->incr('money_total_out', $amount);

        // 余额变更-模板消息通知
        Model_Weixin_TplMsg_Biz::walletMoneyChanged($this->_uid, -$amount, $this->_user['money']);

        return true;
    }

    /**
     * 发起提现申请
     *
     * @param int $amount 消耗金额（分）
     * @return bool
     */
    public function sendWithdrawReq($amount)
    {
        // 提现金额判断
        $this->checkBalance($amount);

        $setArr = [
            'uid'        => $this->_uid,
            'amount'     => $amount,
            'status'     => 0,
            'created_at' => $GLOBALS['_DATE'],
        ];

        // 提交申请
        if (! $reqId = Dao('Massive_LogWithdrawApply')->insert($setArr)) {
            return false;
        }

        // 冻结指定数量的账户余额
        $this->consume($amount, 50803, '申请提现，申请ID:' . $reqId);

        return $reqId;
    }

    /**
     * 同意提现申请
     *
     * @param int $reqId 提现申请ID
     * @return bool
     */
    public static function passWithdraw($reqId)
    {
        $reqInfo = Dao('Massive_LogWithdrawApply')->get($reqId);

        if (! $reqInfo) {
            throws('提现申请不存在');
        }

        if ($reqInfo['status'] != 0) {
            throws('该笔提现申请已处理');
        }

        // 标记为“已通过”
        $result = Dao('Massive_LogWithdrawApply')->updateByPk([
            'status'     => 1,
            'checked_at' => $GLOBALS['_DATE'],
        ], $reqId, [
            'status' => 0,  // 防止并发
        ]);

        if (! $result) {
            return false;
        }

        // 微信企业转账
        Model_Weixin_MchPay_Base::push([
            'action'   => 60901,
            'recv_uid' => $reqInfo['uid'],
            'money'    => $reqInfo['amount'],
            'desc'     => 'Stay-账户余额提现',
        ]);

        return true;
    }

    /**
     * 否决提现申请
     *
     * @param int $reqId 提现申请ID
     * @return bool
     */
    public static function rejectWithdraw($reqId, $reason)
    {
        $reqInfo = Dao('Massive_LogWithdrawApply')->get($reqId);

        if (! $reqInfo) {
            throws('提现申请不存在');
        }

        if ($reqInfo['status'] != 0) {
            throws('该笔提现申请已处理');
        }

        // 标记为“已拒绝”
        $result = Dao('Massive_LogWithdrawApply')->updateByPk([
            'status'     => 2,
            'reason'     => $reason,
            'checked_at' => $GLOBALS['_DATE'],
        ], $reqId, [
            'status' => 0,  // 防止并发
        ]);

        if (! $result) {
            return false;
        }

        // 将被冻结的金额返还给申请人
        $user = new Model_User($reqInfo['uid']);
        $user->wallet->deposit($reqInfo['amount'], 50098, '提现被拒，申请ID:' . $reqId);

        // 模板通知申请人
        Model_Weixin_TplMsg_Base::push([
            'recv_uid' => $reqInfo['uid'],
            'title'    => '尊敬的用户，您的提现申请已处理。',
            'tpl_id'   => 'WITHDRAW_REQ_REJECTED',
            'url'      => sUrl('/wallet'),
            'keywords' => [
                $reqInfo['amount'] / 100,
                '微信零钱',
                $reqInfo['created_at'],
                '审核未通过',
                $GLOBALS['_DATE'],
            ],
            'remark'   => '您的提现审核没有通过审核' . ($reason ? '，失败原因：' . $reason : '') . '，有任何疑问，请致电客服。',
        ]);

        return true;
    }

    /**
     * 检测账户余额余额
     *
     * @param int $amount
     * @return void
     */
    public function checkBalance($amount)
    {
        if ($amount > $this->_user['money']) {
            throws('NoEnoughMoney');
        }
    }

    // 我的收支明细列表
    public function getLogUserMoney($type, $action, $start, $pageSize)
    {
        // 按条件筛选
        $where = ['uid' => $this->_uid];

        // 1:收入 2:支出
        if ($type) {
            $where['type'] = $type;
        }

        // 具体行为
        if ($action) {
            $where['action'] = $action;
        }

        $list = Dao('Massive_LogUserMoney')->where($where)->limit($start, $pageSize)->order('`id` DESC')->fetchAll();

        foreach ($list as &$value) {
            $value['memos'] = self::$ACTIONS[$value['action']];
        }

        return $list;
    }
}