<?php

/**
 * 微信公众号接收消息
 * 接收语音识别结果
 *
 * @link http://mp.weixin.qq.com/wiki/2/f2bef3230362d18851ee22953abfadde.html
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_Recv_Voice extends Model_Weixin_Recv_Abstract
{
    public function process()
    {
        /*
            <xml>
            <ToUserName><![CDATA[toUser]]></ToUserName>
            <FromUserName><![CDATA[fromUser]]></FromUserName>
            <CreateTime>1357290913</CreateTime>
            <MsgType><![CDATA[voice]]></MsgType>
            <MediaId><![CDATA[media_id]]></MediaId>
            <Format><![CDATA[Format]]></Format>
            <Recognition><![CDATA[腾讯微信团队]]></Recognition>
            <MsgId>1234567890123456</MsgId>
            </xml>
        */
    }
}