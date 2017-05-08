<?php

/**
 * 支付模型-微信支持-抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 */

require_once APP_PATH . 'models/Payment/Util/Weixin/WxPayPubHelper.php';

class Model_Payment_Channel_Weixin_Abstract extends Model_Payment_Channel_Abstract
{
    protected $_appId;

    public function respNotify(array $postData)
    {
        // 存储微信的回调
        $xml = file_get_contents('php://input');

        // 使用通用通知接口
        $notify = new Notify_pub($this->_appId);

        try {

            if (! $xml) {
                throw new Model_Payment_Exception_Common('数据异常-请求数据为空');
            }

            $notify->saveData($xml);

            if (! $notify->checkSign()) {
                throw new Model_Payment_Exception_Common('签名验证失败');
            }

            $result = $notify->getData();

            if ($result['result_code'] != 'SUCCESS' || $result['return_code'] != 'SUCCESS') {
                throw new Model_Payment_Exception_Common('支付状态不正确');
            }

            // 我方订单流水号
            $orderSn = $result['out_trade_no'];

            // 创建订单实例
            $order = $this->_getOrderBySn($orderSn);

            // 更新订单状态
            $order->setPaid([
                'third_buyer_acount' => $result['openid'],              // 买家的第三方账户信息
                'third_order_sn'     => $result['transaction_id'],      // 第三方的订单交易号
                'third_pay_time'     => $result['time_end'],            // 第三方完成支付时间
            ]);

            // 最终结算订单
            $order->setFinished();

            // 微信支付时可以同时关注微信公众号（支付界面右下角有复选框）
            if (isset($result['is_subscribe']) && $result['is_subscribe'] == 'Y') {
                Model_Account_Third::setWxMpSubscribed($result['openid'], 1, 1);
            }

            $notify->setReturnParameter('return_code', 'SUCCESS');
            $notify->setReturnParameter('return_msg', '成功响应通知');

            return $notify->returnXml();
        }

        catch (Model_Payment_Exception_Common $e) {

            $notify->setReturnParameter('return_code', 'FAIL');
            $notify->setReturnParameter('return_msg', $e->getMessage());

            return $notify->returnXml();
        }

        catch (Model_Payment_Exception_Repeat $e) {

            $notify->setReturnParameter('return_code', 'SUCCESS');
            $notify->setReturnParameter('return_msg', '重复请求');

            return $notify->returnXml();
        }
    }
}