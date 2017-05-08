<?php

/**
 * 微信红包-通信接口类
 *
 * @link http://pay.weixin.qq.com/wiki/doc/api/cash_coupon.php?chapter=13_1
 *
 * @author JiangJian <silverd@sohu.com>
 *
 * 红包接口规则
 *
 * - 金额单位：分
 * - 单个红包金额介于1~200元之间
 * - 红包接口仅支持普通红包，且同一个红包只能发送给一个用户
 * - 单个商户日发送红包数量最多10000个
 * - 每分钟发送红包数量不得超过1800个
 * - 北京时间0:00~8:00不能触发红包赠送，API会返回失败
 * - 同一用户每日最多可获取2个红包，每月最多10个
 */

class Model_Weixin_RedPack_Api extends Core_Model_Abstract
{
    // API接口地址
    const REMOTE_API_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';

    /**
     * 发送红包
     *
     * @param  string $recvOpenId 接收人
     * @param  string $title      标题
     * @param  string $wishing    祝福语
     * @param  int $money         红包金额（单位分）
     * @return array
     */
    public static function send($recvOpenId, $title, $wishing, $money)
    {
        $params = [
            // 随机字符串
            'nonce_str'    => Model_Weixin_Util::buildNonceStr(),
            // 我方订单号
            'mch_billno'   => self::__buildOrderNo(WX_MCH_ID),
            // 我方商户号
            'mch_id'       => WX_MCH_ID,
            // 我方微信公众号
            'wxappid'      => WX_MP_APP_ID,
            // 本次活动名称
            'act_name'     => $title,
            // 资金方提供名称
            'nick_name'    => WX_RED_PACK_SENDER,
            // 红包发送者名称
            'send_name'    => WX_RED_PACK_SENDER,
            // 红包接受者
            're_openid'    => $recvOpenId,
            // 红包金额（单位分）
            'total_amount' => $money,
            'min_value'    => $money,
            'max_value'    => $money,
            // 红包发放人数
            'total_num'    => 1,
            // 红包祝福语
            'wishing'      => $wishing,
            // 备注
            'remark'       => $wishing,
            // 客户端IP
            'client_ip'    => self::__getClientIp(),
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