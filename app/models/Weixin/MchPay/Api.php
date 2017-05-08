<?php

/**
 * 微信企业转账-通信接口类
 * 用于企业向微信用户个人付款（进入个人账户的微信零钱）
 *
 * @link https://pay.weixin.qq.com/wiki/doc/api/mch_pay.php?chapter=14_1
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_MchPay_Api extends Core_Model_Abstract
{
    // API接口地址
    const REMOTE_API_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';

    /**
     * 发送红包
     *
     * @param  string $openId 接收人
     * @param  int $money 转账金额（分）
     * @param  string $desc 转账描述（必填，用户可见）
     * @return array
     */
    public static function send($openId, $money, $desc)
    {
        $params = [
            'mch_appid'        => WX_MP_APP_ID,
            'mchid'            => WX_MCH_ID,
            'nonce_str'        => Model_Weixin_Util::buildNonceStr(),
            'partner_trade_no' => self::__buildOrderNo(WX_MCH_ID),
            'openid'           => $openId,
            'check_name'       => 'NO_CHECK',
            're_user_name'     => '',
            'amount'           => $money,
            'desc'             => $desc,
            'spbill_create_ip' => self::__getClientIp(),
        ];

        $params['sign'] = self::__buildSign($params);

        // 将请求参数数组转为XML
        $xml = Helper_String::arrayToXml($params);

        // 向网关发起正式请求
        $result = Model_Weixin_Core::curl(self::REMOTE_API_URL, $xml, true, 'XML');

        // 顺便记录下原始请求参数
        $result['_request_params'] = $params;

        return $result;
    }

    private static function __buildSign(array $params)
    {
        $parmString = Model_Weixin_Util::concatParams($params);

        return strtoupper(md5($parmString . '&key=' . WX_PARTNER_KEY));
    }

    private static function __buildOrderNo($mchId)
    {
        return $mchId . date('Ymd') . str_pad(mt_rand(0, 10000000000), 10, '0', STR_PAD_LEFT);
    }

    private static function __getClientIp()
    {
        $clientIp = Helper_Client::getUserIp();

        if (! $clientIp) {
            $clientIp = Helper_Client::getServerIp();
        }

        if (! $clientIp) {
            $clientIp = '127.0.0.1';
        }

        return $clientIp;
    }
}