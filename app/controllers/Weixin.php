<?php

/**
 * 接收微信POST数据通知
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Controller_Weixin extends Core_Controller_Web
{
    public $yafAutoRender = false;

    // 接收各种消息（普通消息、事件推送等）
    public function recvAction()
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
    public function warningAction()
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

/*
        [{
            "sid": 9527, //短信id （数据类型：64位整型，对应Java和C#的long，不可用int解析)
            "uid": null, //用户自定义id
            "user_receive_time": "2014-03-17 22:55:21", //用户接受时间
            "error_msg": "", //接收失败的原因，如："DB:0103#用户欠费"
            "mobile": "15205201314", //接受手机号
            "report_status": "SUCCESS" //接收状态有:SUCCESS/FAIL/UNKNOWN(未返回)
        },
        {
            "sid": 9528, //（数据类型：64位整型，对应Java和C#的long，不可用int解析)
            "uid": null,
            "user_receive_time": "2014-03-17 22:55:23",
            "error_msg": "",
            "mobile": "15212341234",
            "report_status": "SUCCESS"
        }]
*/

        // 记录日志
        Dao('Massive_LogSmsResult')->insert([
            'data'       => $postData,
            'created_at' => $GLOBALS['_DATE'],
        ]);

        // 请勿修改
        exit('SUCCESS');
    }
}