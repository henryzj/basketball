<?php

/**
 * 支付中心控制器
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Controller_Payment extends Core_Controller_Api_Abstract
{
    // 生成订单
    public function createAction()
    {
        // 下单人
        $uid = $this->getInt('uid');

        // 支付渠道
        $channelId = $this->getInt('channel_id');

        $totalFee    = $this->getx('total_fee');
        $productType = $this->getx('product_type');
        $productInfo = $this->getx('product_info');

        // 支付成功回跳地址
        $returnUrl = $this->getz('return_url');

        try {

            if ($uid < 1) {
                throws403('Invalid Uid');
            }

            // 实例化支付模型
            $payment = Model_Payment::factory($channelId);

            // 生成订单
            $result = $payment->createOrder([
                'uid'          => $uid,
                'total_fee'    => $totalFee,
                'product_type' => $productType,
                'product_info' => $productInfo,
            ]);
        }

        // 输出通知错误和异常
        catch (Exception $e) {
            $this->output($e->getMessage(), -5000);
        }

        // 如果返回内容是一个URL，则直接跳转
        if ($url = Model_Payment_Util::getUrlFromSignal($result)) {
            $this->redirect($url);
        }
        else {

            $bridgeTpl = $payment->getBridgeTpl();

            if (null === $bridgeTpl) {
                exit($result);
            }

            $queryString = http_build_query(array_merge($result, [
                'channel_id' => $channelId,
                'return_url' => $returnUrl,
            ]));

            // 为保持幂等性，避免刷新重复下单，需跳转到另一个页面
            $this->redirect('/payment/bridge/?' . $queryString);
        }
    }

    // 支付桥接页面
    public function bridgeAction()
    {
        $result = $this->getQueryx();

        // 实例化支付模型
        $payment = Model_Payment::factory($result['channel_id']);

        // 防止XSS
        $result['order_sn']   = $this->getz('order_sn');
        $result['return_url'] = $this->getz('return_url');

        $this->assign('result', $result);
        $this->display($payment->getBridgeTpl());

        exit();
    }

    // 同步接收支付平台的回跳
    public function respReturnAction()
    {
        // 当前充值渠道
        $channelId = $this->getInt('channel_id');

        // 实例化支付模型
        $payment = Model_Payment::factory($channelId);

        $postData = array_merge($_GET, $_POST);

        // 增加通知日志记录
        $payment->markRespData($postData, 2);

        try {
            // 响应通知
            $result = $payment->respReturn($postData);
        }
        // 输出通知错误和异常
        catch (Exception $e) {
            exit($payment->handleError($e));
        }

        // 如果返回内容是一个URL，则直接跳转
        if ($url = Model_Payment_Util::getUrlFromSignal($result)) {
            $this->redirect($url);
        }
        else {
            $this->assign('result', $result);
        }
    }

    // 异步接收支付平台的通知
    public function respNotifyAction()
    {
        // 当前充值渠道
        $channelId = $this->getInt('channel_id');

        // 实例化支付模型
        $payment = Model_Payment::factory($channelId);

        $postData = array_merge($_GET, $_POST);

        // 增加通知日志记录
        $payment->markRespData($postData, 0);

        try {
            // 响应通知
            $result = $payment->respNotify($postData);
        }
        // 输出通知错误和异常
        catch (Exception $e) {
            exit($payment->handleError($e));
        }

        // 输出响应结果
        exit($result);
    }

    // TODO 防刷
    // 生成微信扫码支付二维码
    public function makeScanQrCodeAction()
    {
        if (! $codeUrl = $this->getx('code_url')) {
            throws403('Invalid CodeUrl');
        }

        Com_QrCode::make($codeUrl);
    }

    // TODO 防刷
    // 每10秒轮询检测是否已在微信端支付完成
    public function isOrderPaidAction()
    {
        $orderSn = $this->getx('order_sn');
        $order = new Model_Payment_Order($orderSn);

        // 已成功支付
        if ($order->isPaid() && $order->isCbSucceed()) {
            $this->output('YES');
        }

        $this->output('NO');
    }
}