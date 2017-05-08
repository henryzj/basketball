<?php

/**
 * 微信企业转账-应用类
 *
 * @link https://pay.weixin.qq.com/wiki/doc/api/mch_pay.php?chapter=14_1
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_MchPay_Base extends Core_Model_Abstract
{
    // 使用场景
    public static $ACTIONS = [
        60901 => '账户余额提现',
    ];

    // 将一个任务塞进队列
    public static function push(array $params)
    {
        // 检测参数
        $params = self::check($params);

        $data = json_encode([
            'action'      => $params['action'],
            'recv_uid'    => $params['recv_uid'],
            'recv_openid' => $params['recv_openid'],
            'money'       => $params['money'],  // 单位：分，必须大于1元
            'desc'        => $params['desc'],
            'memos'       => $params['memos'],
        ], JSON_UNESCAPED_UNICODE);

        return S('Model_Queue_MchPay')->push($data);
    }

    public static function check(array $params)
    {
        $params['action']      = isset($params['action'])      ? $params['action']      : 0;
        $params['recv_uid']    = isset($params['recv_uid'])    ? $params['recv_uid']    : 0;
        $params['recv_openid'] = isset($params['recv_openid']) ? $params['recv_openid'] : '';
        $params['money']       = isset($params['money'])       ? $params['money']       : 0;
        $params['desc']        = isset($params['desc'])        ? $params['desc']        : '';
        $params['memos']       = isset($params['memos'])       ? $params['memos']       : '';

        if (! isset(self::$ACTIONS[$params['action']])) {
            throw new Model_Weixin_Exception('未定义的企业转账场景');
        }

        if (! $params['desc']) {
            throw new Model_Weixin_Exception('企业转账备注不能为空');
        }

        if (! $params['recv_openid'] && $params['recv_uid']) {
            $params['recv_openid'] = Model_Account_Third::getWxOpenId($params['recv_uid']);
        }
        elseif (! $params['recv_uid'] && $params['recv_openid']) {
            $params['recv_uid'] = Model_Account_Third::getUidByWxOpenId($params['recv_openid']);
        }

        if (! $params['recv_openid'] || ! $params['recv_uid']) {
            throw new Model_Weixin_Exception('企业转账收件人不能为空');
        }

        if (! $params['memos']) {
            $params['memos'] = $params['desc'];
        }

        return $params;
    }

    // 不经过队列直接调用微信API转账
    public static function sendDirect(array $params)
    {
        // 检测参数
        $params = self::check($params);

        $result = Model_Weixin_MchPay_Api::send(
            $params['recv_openid'],
            $params['money'],
            $params['desc']
        );

        // 发送结果
        $isOk = ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') ? 1 : 0;

        if ($isOk) {
            // 为便于财务对账
            // 这里再额外记录发送成功的记录
            Com_Logger_Redis::custom('mchPayFinacial', [
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