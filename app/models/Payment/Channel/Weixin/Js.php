<?php

/**
 * 支付模型-微信公众号H5-JsApi
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Payment_Channel_Weixin_Js extends Model_Payment_Channel_Weixin_Abstract
{
    protected $_appId = WX_MP_APP_ID;

    protected function _buildCreateReturn(Model_Payment_Order $order, Model_Payment_Product_Abstract $product)
    {
        // 下单人的微信公众平台账号
        $openId = Model_Account_Third::getWxOpenId($order['uid'], 'MP', $this->_appId);

        $unifiedOrder = new UnifiedOrder_pub($this->_appId);

        // 设置统一支付接口参数
        $unifiedOrder->setParameter('openid', $openId);
        $unifiedOrder->setParameter('body', $product->getTitle());

        $unifiedOrder->setParameter('notify_url', WX_PAY_NOTIFY_URL . $this->_channelId);
        $unifiedOrder->setParameter('trade_type', 'JSAPI');
        $unifiedOrder->setParameter('device_info', 'WEB');
        $unifiedOrder->setParameter('out_trade_no', $order['order_sn']);

        // 订单总价（单位：分）
        $unifiedOrder->setParameter('total_fee', 100 * $order['total_fee']);

        // 调用微信API进行下单
        // 返回预支付交易会话标识
        $prepayId = $unifiedOrder->getPrepayId();

        // 构造响应JSON字符串
        $jsApi = new JsApi_pub($this->_appId);
        $jsApi->setPrepayId($prepayId);
        $jsApiParameters = $jsApi->getParameters();

        return $jsApiParameters;
    }
}