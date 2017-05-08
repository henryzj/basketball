<?php

/**
 * 微信公众号接收消息-工厂
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_Recv
{
    public static function getHandler()
    {
        $data = self::getData();

        static $models = [
            'event' => 'Model_Weixin_Recv_Event',
            'voice' => 'Model_Weixin_Recv_Voice',
        ];

        $msgType = $data['MsgType'];

        if (isset($models[$msgType])) {
            $className = $models[$msgType];
        }
        else {
            $className = 'Model_Weixin_Recv_Common';
        }

        return new $className($data);
    }

    public static function getData()
    {
        $dataStr = file_get_contents('php://input');

        if (! $dataStr) {
            throws('请求内容为空');
        }

        // 微信收发日志
        addWeixinLog($dataStr, 0);

        $dataArr = Helper_String::xmlToArray($dataStr);

        if (! $dataArr) {
            throws('XML转换数组失败');
        }

        return $dataArr;
    }

    // doc: http://mp.weixin.qq.com/wiki/4/2ccadaef44fe1e4b0322355c2312bfa8.html
    public static function verifySign(array $data)
    {
        if (! isset($data['signature']) || empty($data['signature'])
         || ! isset($data['timestamp']) || empty($data['timestamp'])
         || ! isset($data['nonce'])     || empty($data['nonce'])
        ) {
            return false;
        }

        $params = [
            WX_RECV_TOKEN,
            $data['timestamp'],
            $data['nonce']
        ];

        sort($params, SORT_STRING);
        $tmpStr = sha1(implode($params));

        return $tmpStr == $data['signature'] ? 1 : 0;
    }
}