<?php

class Controller_Resp extends Core_Controller_Api_Abstract
{
    // 七牛异步处理
    public function respQiniuCallbackAction()
    {
        $callbackUrl = 'http://' . $GLOBALS['SITE_HOST'] . '/mapi/resp/respQiniuCallback/';

        $this->json(Com_Qiniu::respCallback($callbackUrl));
    }

    // 接收微信POST数据通知、各种消息（普通消息、事件推送等）
    public function weixinRecvAction()
    {
        // 仅第一次接入需要验证
        if (isset($_GET['echostr'])) {
            if (Model_Weixin_Recv::verifySign($_GET)) {
                exit(strval($_GET['echostr']));
            }
        }

        $handler = Model_Weixin_Recv::getHandler();
        $handler->process();
    }

    // 微信支付告警通知URL
    // 微信监测到商户服务出现问题时，会及时推送相关告警信息到商户后台
    public function weixinWarningAction()
    {
        // 读取原生RAW-POST数据
        if (! $data = file_get_contents('php://input')) {
            exit('Empty Warning Data');
        }

        // 记录日志
        Dao('Massive_LogWeixinWarning')->insert([
            'data'       => $data,
            'created_at' => $GLOBALS['_DATE'],
        ]);

        exit('OK');
    }

    // 接收云片SMS短信发送结果通知
    public function yunPianSmsAction()
    {
        $postData = rawurldecode($this->getPost('sms_status'));

        // 记录日志
        Dao('Massive_LogSmsResult')->insert([
            'data'       => $postData,
            'created_at' => $GLOBALS['_DATE'],
        ]);

        // 请勿修改
        exit('SUCCESS');
    }
}