<?php

/**
 * 微信红包-应用类
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_RedPack_Base extends Core_Model_Abstract
{
    // 红包发放场景
    public static $ACTIONS = [
        // 50901 => '账户余额提现', # 2015.07.15 提现改用企业转账-微信零钱
    ];

    // 将一个任务塞进队列
    public static function push(array $params)
    {
        // 检测参数
        $params = self::check($params);

        // 根据金额处理拆分大于200元的红包
        $packs = self::__splitBigRedPack($params['money']);

        $total = count($packs);

        for ($i = 0; $i < $total; $i++) {

            $data = json_encode([
                'action'      => $params['action'],
                'recv_uid'    => $params['recv_uid'],
                'recv_openid' => $params['recv_openid'],
                'title'       => $params['title'],
                'wishing'     => $params['wishing'],
                'money'       => $packs[$i],
                'memos'       => $total > 1 ? ($params['memos'] . ' -- 大红包拆分 ' . ($i + 1). '|' . $total) : $params['memos'],
            ], JSON_UNESCAPED_UNICODE);

            S('Model_Queue_RedPack')->push($data);
        }

        return true;
    }

    // 根据金额处理拆分大于200元的红包
    private static function __splitBigRedPack($money)
    {
        $packs = [];

        $MIN = 100;    // 单个红包最小值1元
        $MAX = 20000;  // 单个红包最大值200元

        // 小于200元不需要拆分
        if ($money <= $MAX) {
            $packs[] = $money;
            return $packs;
        }

        // 如果红包大于200，则将它拆分成几个红包

        $redPackNum = floor($money / $MAX);
        $leftMoney  = $money % $MAX;

        for ($i = 0; $i < $redPackNum; $i++) {
            $packs[] = $MAX;
        }

        // 如果最后一个红包小于1元，则把倒数第二个红包的钱挪1元到最后一个红包上
        if ($leftMoney > 0) {
            if ($leftMoney < $MIN) {
                $packs[count($packs) -1] -= $MIN;
                $leftMoney += $MIN;
            }

            $packs[] = $leftMoney;
        }

        return $packs;
    }

    public static function check(array $params)
    {
        $params['action']      = isset($params['action'])      ? $params['action']      : 0;
        $params['recv_uid']    = isset($params['recv_uid'])    ? $params['recv_uid']    : 0;
        $params['recv_openid'] = isset($params['recv_openid']) ? $params['recv_openid'] : '';
        $params['title']       = isset($params['title'])       ? $params['title']       : '';
        $params['wishing']     = isset($params['wishing'])     ? $params['wishing']     : '';
        $params['money']       = isset($params['money'])       ? $params['money']       : 0;
        $params['memos']       = isset($params['memos'])       ? $params['memos']       : '';

        if (! isset(self::$ACTIONS[$params['action']])) {
            throw new Model_Weixin_Exception('未定义的红包发放场景');
        }

        if (! $params['title'] || ! $params['wishing']) {
            throw new Model_Weixin_Exception('红包标题和祝福语不能为空');
        }

        if (Helper_String::strlen($params['wishing']) >= 128) {
            throw new Model_Weixin_Exception('红包祝福语太长，无法发送');
        }

        if ($params['money'] < 100) {
            throw new Model_Weixin_Exception('红包金额必须大于1元');
        }

        if (! $params['recv_openid'] && $params['recv_uid']) {
            $params['recv_openid'] = Model_Account_Third::getWxOpenId($params['recv_uid']);
        }
        elseif (! $params['recv_uid'] && $params['recv_openid']) {
            $params['recv_uid'] = Model_Account_Third::getUidByWxOpenId($params['recv_openid']);
        }

        if (! $params['recv_openid'] || ! $params['recv_uid']) {
            throw new Model_Weixin_Exception('红包收件人不能为空');
        }

        return $params;
    }

    // 不经过队列直接调用微信API发红包
    public static function sendDirect(array $params)
    {
        // 检测参数
        $params = self::check($params);

        $result = Model_Weixin_RedPack_Api::send(
            $params['recv_openid'],
            $params['title'],
            $params['wishing'],
            $params['money']
        );

        // 发送结果
        $isOk = ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') ? 1 : 0;

        if ($isOk) {
            // 为便于财务对账
            // 这里再额外记录发送成功的红包记录
            Com_Logger_Redis::custom('redPackFinacial', [
                'action'      => $params['action'],
                'recv_uid'    => $params['recv_uid'],
                'recv_openid' => $params['recv_openid'],
                'money'       => $params['money'],
                'memos'       => $params['memos'],
                'created_at'  => $GLOBALS['_DATE'],
            ]);
        }

        return [
            'is_ok'      => $isOk,
            'return_msg' => $result,
        ];
    }
}