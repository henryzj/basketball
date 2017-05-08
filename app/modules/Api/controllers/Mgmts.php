<?php

/**
 * Api 服务端控制器
 * 提供给GM管理后台的
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Mgmts.php 11936 2014-07-28 09:10:13Z zhengjiang $
 */

class Controller_Mgmts extends Core_Controller_Api_Simple
{
    // 签名私钥
    protected $_secretKey = 'a18de99235589c5904c544fd2763d7f7';

    // 获取充值渠道列表
    // 提供给 Ucenter GM后台专用API
    public function getPayChannelsAction()
    {
        $this->output(Model_Payment::$channels);
    }

    // 将指定订单手动推送一次回调
    // 提供给 Payment GM后台专用API
    public function pushOrderCbAction()
    {
        $orderSn = $this->getx('order_sn');

        $order  = new Model_Payment_Order($orderSn);
        $result = $order->setFinished();

        $this->output($result[0] . ' ' . $result[1]);
    }

    // 酒店订单状态们
    public function getHotelOrderStatusAction()
    {
        $this->output(Model_Hotel_Order::$statusTexts);
    }

    // 系统自动释放订单
    public function autoDropOrderAction()
    {
        $orderSn = $this->getx('order_sn');
        $reason = $this->get('reason');

        $result = Model_Hotel_Asst::autoDropOrder($orderSn, $reason);

        $this->output($result);
    }

    // 发送系统消息
    public function sendSysBroadcastAction()
    {
        $content    = $this->getx('content');
        $targetInfo = $this->getx('target_info');
        $pushMsg    = $this->getx('push_msg');

        $result = Model_Stay_Message::sendSysBroadcast($content, $targetInfo, $pushMsg);

        $this->output($result);
    }

    // 发送系统单播消息
    public function sendSysUnicastAction()
    {
        $recvUid    = $this->getx('recv_uid');
        $content    = $this->getx('content');
        $targetInfo = $this->getx('target_info');
        $pushMsg    = $this->getx('push_msg');

        $result = Model_Stay_Message::sendSysUnicast($recvUid, $content, $targetInfo, $pushMsg);

        $this->output($result);
    }
}