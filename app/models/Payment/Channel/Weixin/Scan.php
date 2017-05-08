<?php

/**
 * 支付模型-微信扫码支付
 * 即：微信Native（原生）支付-模式二
 *
 * 商户先调用统一支付接口获取到 code_url，把此URL生成二维码，用户扫码后调起支付
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Payment_Channel_Weixin_Scan extends Model_Payment_Channel_Weixin_Abstract
{
    protected $_appId = WX_MP_APP_ID;

    protected $_bridgeTpl = 'create-wx-scan';

    protected function _buildCreateReturn(Model_Payment_Order $order, Model_Payment_Product_Abstract $product)
    {
        $unifiedOrder = new UnifiedOrder_pub($this->_appId);

        // 设置统一支付接口参数
        $unifiedOrder->setParameter('body', $product->getTitle());

        $unifiedOrder->setParameter('notify_url', WX_PAY_NOTIFY_URL . $this->_channelId);
        $unifiedOrder->setParameter('trade_type', 'NATIVE');
        $unifiedOrder->setParameter('out_trade_no', $order['order_sn']);
        $unifiedOrder->setParameter('product_id', $order['product_type'] . ':' . $order['product_info']);

        // 订单总价（单位：分）
        $unifiedOrder->setParameter('total_fee', 100 * $order['total_fee']);

        // 调用微信API进行下单
        // 返回扫码URL链接 weixin://wxpay/bizpayurl?sr=XXXXX
        $codeUrl = $unifiedOrder->getCodeUrl();

        return [
            'code_url'  => $codeUrl,
            'order_sn'  => $order['order_sn'],
            'total_fee' => $order['total_fee'],
        ];
    }
}