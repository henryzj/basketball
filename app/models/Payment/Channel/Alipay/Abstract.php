<?php

/**
 * 支付模型-支付宝父类
 *
 * @author sunli
 * $Id: Abstract.php 9981 2014-03-21 09:15:56Z jiangjian $
 */

Yaf_Loader::import(SYS_PATH . 'Third/Alipay/alipay_submit.class.php');
Yaf_Loader::import(SYS_PATH . 'Third/Alipay/alipay_notify.class.php');

class Model_Payment_Channel_Alipay_Abstract extends Model_Payment_Channel_Abstract
{
    protected function _loadConfig()
    {
        $this->_config = include CONF_PATH . 'alipay/config.php';
    }

    protected function _verifyRespData(array $postData)
    {
        $alipayNotify = new AlipayNotify($this->_config);

        if (! $alipayNotify->verifyNotify($postData)) {
            throw new Model_Payment_Exception_Common('Invalid Response Data');
        }
    }

    public function respNotify(array $postData)
    {
        // 检测返回数据合法性
        $this->_verifyRespData($postData);

        // 此订单已经支付完成
        if ($postData['trade_status'] == 'TRADE_FINISHED' || $postData['trade_status'] == 'TRADE_SUCCESS') {

            // 我方订单流水号
            $orderSn = $postData['out_trade_no'];

            try {
                // 创建订单实例
                $order = $this->_getOrderBySn($orderSn);
            }
            catch (Model_Payment_Exception_Repeat $e) {
                // 重复通知
                return 'success';
            }

            // 更新订单状态
            $order->setPaid(array(
                'third_order_sn'     => $postData['trade_no'],         // 第三方的订单交易号
                'third_pay_time'     => $postData['gmt_payment'],      // 购买时间
                'third_buyer_id'     => $postData['buyer_id'],         // 买家的第三方id
                'third_buyer_acount' => $postData['buyer_email'],      // 买家的第三方账户名称 (邮箱或者手机号)
            ));

            // 最终结算订单（给充值人加金块等）
            $this->_finishOrder($order);
        }

        return 'success';   // 请勿修改，支付宝约定响应结果
    }
}