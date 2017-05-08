<?php

/**
 * 百度云推送
 *
 * @link http://push.baidu.com/doc/php/api
 *
 * @author JiangJian <silverd@sohu.com>
 */

Yaf_Loader::import(SYS_PATH . 'Third/BaiduPushServerSDK/sdk.php');

class Com_Push_BaiduPush
{
    protected $_sdk;

    public function __construct($apiKey, $secretKey)
    {
        $this->_sdk = new PushSDK($apiKey, $secretKey);
    }

    protected function _parseResult($result)
    {
        //  推送失败
        if ($result === false) {
            $errMsg  = $this->_sdk->getLastErrorMsg();
            $errCode = $this->_sdk->getLastErrorCode();
            $logId   = $this->_sdk->getRequestId();
            throws("Call BaiduPushSDK Failed：[RequestId:$logId][error:$errCode,$errMsg]");
        }

        return $result;
    }

    // 单播：根据channel_id向单个设备推送消息
    // 广播：向当前应用下所有设备发送一条消息
    // 组播：向一个指定的组内的所有设备发送一条消息
    // 批量单播：向一组指定的设备channel_ids发送一条消息

    public function __call($method, $args)
    {
        $result = call_user_func_array([$this->_sdk, $method], $args);

        return $this->_parseResult($result);
    }
}