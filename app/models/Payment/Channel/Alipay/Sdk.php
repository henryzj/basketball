<?php

/**
 * 支付模型-支付宝SDK版
 *
 * @author sunli
 * $Id: Sdk.php 7937 2014-01-13 06:31:27Z jiangjian $
 */

class Model_Payment_Channel_Alipay_Sdk extends Model_Payment_Channel_Alipay_Abstract
{
    /**
     * 组装生成订单后的返回结果
     * 以JSON格式输出响应给客户端
     *
     * @param Model_Payment_Order $order
     * @param Model_Payment_Product $product
     * @return string $json
     */
    protected function _buildCreateReturn(Model_Payment_Order $order, Model_Payment_Product_Abstract $product)
    {
        $params = array(
            "partner"        => $this->_config['partner'],
            'out_trade_no'   => $order['order_sn'],              // 内部订单流水号
            'subject'        => $product['name'],                // 订单名称
            "body"           => _('购买') . $product['name'],    // 订单描述
            'total_fee'      => $order['total_fee'],             // 订单总价
            "notify_url"     => urlencode($this->_config['notify_url']),    // 服务器异步通知页面路径
            "service"        => "mobile.securitypay.pay",        // 请勿修改
            "_input_charset" => strtolower($this->_config['input_charset']),  // 设置编码
            "payment_type"   => 1,                               // 请勿修改
            "seller_id"      => $this->_config['seller_id'],     // 卖家支付宝帐户
            'app_id'         => '',                              // 可空，客户端号
            'appenv'         => '',                              // 可空，客户端来源
            'it_b_pay'       => '',                              // 可空，未付款交易的超时时间
            'show_url'       => '',                              // 可空，商品展示地址
            'extern_token'   => '',                              // 可空，授权令牌
        );

        return json_encode([
            'status'   => 'success',
            'paramStr' => $this->_buildSignString($params),
        ]);
    }

    // 签名并拼接请求参数字符串
    protected function _buildSignString(array $params)
    {
        $strs = array();

        foreach ($params as $key => $value) {
            if ($value) {
                $strs[] = $key . '="' . $value . '"';
            }
        }

        // 拼接待签名字符串
        $paramStr = implode('&', $strs);

        // 构造RSA签名
        $sign = rsaSign($paramStr, $this->_config['private_key_path']);

        // 返回最终完整参数串
        return $paramStr . '&sign="' . urlencode($sign) . '"&sign_type="RSA"';
    }
}