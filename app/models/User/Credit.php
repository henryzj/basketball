<?php

/**
 * 我的积分
 *
 * @author JiangJian & UncleZ <silverd@sohu.com>
 */

class Model_User_Credit extends Model_User_Trait
{
    const TYPE_IN  = 1; // 收入
    const TYPE_OUT = 2; // 支出

    // 积分场景
    public static $ACTIONS = [

        // 加
        60101 => '发布职位',
        60102 => '推荐BigCard用户',
        60103 => '推荐有简历大咖',
        60104 => '推荐无简历大咖',
        60105 => '推荐的大咖被搭讪',
        60106 => '邀约成功',
        60107 => '神秘奖励',

        // 减
        60801 => '搭讪陌生大咖',
    ];

    public function add($amount, $action, $memos = '')
    {
        if ($amount < 1) {
            return false;
        }

        if (! isset(self::$ACTIONS[$action])) {
            return false;
        }

        // 执行增加
        $this->_user->update(['credits'=> ['+', $amount]]);

        // 增加消费日志
        Dao('Massive_LogUserCredit')->insert([
            'uid'        => $this->_uid,
            'type'       => self::TYPE_IN,
            'action'     => $action,
            'credits'    => $amount,
            'memos'      => $memos,
            'created_at' => $GLOBALS['_DATE'],
        ]);

        // 增加用户数据统计
        $this->_user->stats->incr('credit_total_in', $amount);

        // 每小时行为统计
        $this->_user->hourlyStats->incr('credit_incr', $amount);

        // 积分变更-模板消息通知
        Model_Weixin_TplMsg_Biz::creditChanged($this->_uid, $amount, self::$ACTIONS[$action], $this->_user['credits']);

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
        $this->_user->update(['credits'=> ['-', $amount]]);

        // 增加消费日志
        Dao('Massive_LogUserCredit')->insert([
            'uid'        => $this->_uid,
            'type'       => self::TYPE_OUT,
            'action'     => $action,
            'credits'    => $amount,
            'memos'      => $memos,
            'created_at' => $GLOBALS['_DATE'],
        ]);

         // 增加用户数据统计
        $this->_user->stats->incr('credit_total_out', $amount);

        // 积分变更-模板消息通知
        Model_Weixin_TplMsg_Biz::creditChanged($this->_uid, -$amount, self::$ACTIONS[$action], $this->_user['credits']);

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
        if ($amount > $this->_user['credits']) {
            throws('NoEnoughCredit');
        }
    }

    // 我的收支明细列表
    public function getLogUserCredit($type, $action, $start, $pageSize)
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

        $list = Dao('Massive_LogUserCredit')->where($where)->limit($start, $pageSize)->order('`id` DESC')->fetchAll();

        foreach ($list as &$value) {
            if (self::TYPE_IN == $value['type']) {
                // 备注文案
                $value['memos'] = '得到「' . self::$ACTIONS[$value['action']] . '」积分';
            }
            elseif (self::TYPE_OUT == $value['type']) {
                // 备注文案
                $value['memos'] = self::$ACTIONS[$value['action']];
            }
        }

        return $list;
    }
}