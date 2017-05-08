<?php

/**
 * 极光推送
 *
 * @link http://docs.jpush.io/server/rest_api_v3_push/
 *
 * @author zhengjiang
 */

Yaf_Loader::import(SYS_PATH . 'vendor/jpush/jpush/src/JPush/JPush.php');

class Com_Push_JPush
{
    protected $_client;

    public function __construct($appKey, $appSecret)
    {
        // 生产环境不记录日志
        $logFile = isDebug() ? (LOG_PATH . 'jpush.log') : null;

        $this->_client = new JPush($appKey, $appSecret, $logFile);
    }

    // 单播：根据channel_id向单个设备推送消息
    public function pushMsgToSingleDevice($pushChannelId, $alert, array $extra = [])
    {
        return $this->__pushToPayload($alert, function ($payload) use ($pushChannelId) {
            $payload->addRegistrationId($pushChannelId);
        }, $extra);
    }

    // 广播：向当前应用下所有设备发送一条消息
    public function pushMsgToAll($alert, array $extra = [])
    {
        return $this->__pushToPayload($alert, function ($payload) {
            $payload->setAudience('all');
        }, $extra);
    }

    // 组播：向一个指定的组内的所有设备发送一条消息
    public function pushMsgToTag($tagName, $alert, array $extra = [])
    {
        return $this->__pushToPayload($alert, function ($payload) {
            $payload->addTags($tagName);
        }, $extra);
    }

    // 批量单播：向一组指定的设备channel_ids发送一条消息
    public function pushBatchUniMsg(array $pushChannelIds, $alert, array $extra = [])
    {
        return $this->pushMsgToSingleDevice($pushChannelIds, $alert, $extra);
    }

    private function __pushToPayload($alert, callable $callback, array $extra = [])
    {
        try {

            $payload = $this->_client->push();

            // 全平台（iOS+Android）
            $payload->setPlatform('all');

            // 业务回调
            $callback($payload);

            // 给所有平台推送相同的消息
            if ($extra) {
                $payload->addAndroidNotification($alert, null, null, null, null, $extra);
                $payload->addIosNotification($alert, null, null, $extra);
            }
            else {
                $payload->setNotificationAlert($alert);
            }

            // 生产环境 apns_production=true
            if (! isDebug()) {
                $payload->setOptions(null, null, null, true);
            }

            $result = $payload->send();

            return [
                'status' => 0,
                'sendNo' => $result->data->sendno,
                'msgId'  => $result->data->msg_id,
            ];
        }

        catch (APIRequestException $e) {

            return [
                'status'             => -1,
                'httpCode'           => $e->httpCode,
                'errCode'            => $e->code,
                'errMessage'         => $e->message,
                'rateLimitLimit'     => $e->rateLimitLimit,
                'rateLimitRemaining' => $e->rateLimitRemaining,
                'rateLimitReset'     => $e->rateLimitReset,
            ];

        }

        catch (APIConnectionException $e) {

            return [
                'status'            => -2,
                'errMessage'        => $e->getMessage(),
                'IsResponseTimeout' => $e->isResponseTimeout,
            ];
        }
    }
}