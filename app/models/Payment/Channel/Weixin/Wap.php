<?php

/**
 * 支付模型-微信WAP支付（非微信内浏览器支付）
 * WAP支付是基于公众号基础开发的一种非微信内浏览器支付方式（需要单独申请支付权限）
 *
 * @author JiangJian <silverd@sohu.com>
 * @link https://pay.weixin.qq.com/wiki/doc/api/wap.php?chapter=15_4
 */

class Model_Payment_Channel_Weixin_Wap extends Model_Payment_Channel_Weixin_Abstract
{
    protected $_appId = WX_MP_APP_ID;

    protected function _buildCreateReturn(Model_Payment_Order $order, Model_Payment_Product_Abstract $product)
    {
        $unifiedOrder = new UnifiedOrder_pub($this->_appId);

        // 设置统一支付接口参数
        $unifiedOrder->setParameter('body', $product->getTitle());

        $unifiedOrder->setParameter('notify_url', WX_PAY_NOTIFY_URL . $this->_channelId);
        $unifiedOrder->setParameter('trade_type', 'WAP');
        $unifiedOrder->setParameter('device_info', 'WEB');
        $unifiedOrder->setParameter('out_trade_no', $order['order_sn']);

        // 订单总价（单位：分）
        $unifiedOrder->setParameter('total_fee', 100 * $order['total_fee']);

        // 调用微信API进行下单
        // 返回预支付交易会话标识
        $prepayId = $unifiedOrder->getPrepayId();

        // 构造响应JSON字符串
        $jsApi = new Wap_pub($this->_appId);
        $jsApi->setPrepayId($prepayId);
        $deepLink = $jsApi->getDeepLink();

        return json_encode(['deepLink' => $deepLink]);
    }
}