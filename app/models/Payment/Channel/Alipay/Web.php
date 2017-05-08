<?php

/**
 * 支付模型-支付宝网页版
 *
 * @author sunli
 * $Id: Web.php 7937 2014-01-13 06:31:27Z jiangjian $
 */

class Model_Payment_Channel_Alipay_Web extends Model_Payment_Channel_Alipay_Abstract
{
    /**
     * 组装生成订单后的返回结果
     * 以HTML表单格式自动提交GET/POST请求
     *
     * @param Model_Payment_Order $order
     * @param Model_Payment_Product $product
     * @return string $html
     */
    protected function _buildCreateReturn(Model_Payment_Order $order, Model_Payment_Product_Abstract $product)
    {
        $params = [
            "service"           => "create_direct_pay_by_user",         // 请勿修改
            "partner"           => $this->_config['partner'],
            "payment_type"      => 1,                                   // 请勿修改
            "notify_url"        => $this->_config['notify_url'],        // 服务器异步通知页面路径
            "return_url"        => $this->_config['return_url'],        // 页面跳转同步通知页面路径
            "seller_id"         => $this->_config['seller_id'],         // 卖家支付宝帐户
            "out_trade_no"      => $order['order_sn'],                  // 我方订单流水号
            "total_fee"         => $order['total_fee'],                 // 订单总价格
            "subject"           => $product['name'],                    // 订单名称
            "body"              => _('购买') . $product['name'],        // 订单描述
            "show_url"          => $this->_config['show_url'],          // 商品展示地址
            "anti_phishing_key" => '',
            "exter_invoke_ip"   => '',
            "_input_charset"    => strtolower($this->_config['input_charset']),
        ];

        // 构建表单HTML
        $alipaySubmit = new AlipaySubmit($this->_config);
        $html = $alipaySubmit->buildRequestForm($params, 'GET');

        return $html;
    }

    /**
     * 支付宝页面跳转同步通知服务器
     *
     * @param array $postData
     * @return array
     */
    protected function __respReturn(array $postData)
    {
        // 检测返回数据合法性
        $this->_verifyRespData($postData);

        if ($postData['is_success'] != 'T') {
            throw new Model_Payment_Exception_Common('Invalid Response Data 001', -1);
        }

        // 此订单已经支付完成
        if ($postData['trade_status'] != 'TRADE_FINISHED' && $postData['trade_status'] != 'TRADE_SUCCESS') {
            throw new Model_Payment_Exception_Common('Invalid Response Data 002', -2);
        }

        return [
            'order_sn'    => $postData['out_trade_no'],
            'total_price' => $postData['total_fee'],
        ];
    }
}