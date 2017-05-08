<?php

/**
 * 消息推送
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Push extends Core_Model_Abstract
{
    // 使用哪种方式
    protected static $_adapter = 'JPush';

    // 设置适配器
    public static function setAdapter($adapter)
    {
        self::$_adapter = $adapter;
    }

    // 单播：根据channel_id向单个设备推送消息
    public static function pushMsgToSingleDevice(Model_User $user, $alert, array $opts = [])
    {
        if (! isset($user['push_channel_id']) || ! $user['push_channel_id']) {
            return false;
        }

        // 极光推送
        if (self::$_adapter == 'JPush') {

            $pusher = new Com_Push_JPush(JPUSH_APP_KEY, JPUSH_APP_SECRET);

            return $pusher->pushMsgToSingleDevice($user['push_channel_id'], $alert);
        }

        // 百度云推送
        // @link http://push.baidu.com/doc/restapi/msg_struct
        else {

            if (! $pushAppId = Dao('Core_V2AppInfo')->getField($user['app_id'], 'push_app_id')) {
                throws('Invalid BaiduPushAppId');
            }

            $config = $GLOBALS['_BAIDU_PUSH_APPS'][$pushAppId];

            $pusher = new Com_Push_BaiduPush($config['api_key'], $config['secret_key']);
            $deviceType = strtolower($config['device']);

            $msg = [
                'android' => [
                    'title'       => $GLOBALS['SITE_NAME'] . '消息',
                    'description' => $alert,
                ],
                'ios' => [
                    'aps' => [
                        'alert' => $alert,
                    ],
                ],
            ];

            return $pusher->pushMsgToSingleDevice($user['push_channel_id'], $msg[$deviceType], $opts[$deviceType]);
        }
    }

    // 广播：向所有设备推送消息
    public static function pushMsgToAll($alert, array $opts = [])
    {
        // 极光推送
        if (self::$_adapter == 'JPush') {

            $pusher = new Com_Push_JPush(JPUSH_APP_KEY, JPUSH_APP_SECRET);

            return $pusher->pushMsgToAll($alert);
        }

        // 百度云推送
        // @link http://push.baidu.com/doc/restapi/msg_struct
        else {

            $msg = [
                'android' => [
                    'title'       => $GLOBALS['SITE_NAME'] . '消息',
                    'description' => $alert,
                ],
                'ios' => [
                    'aps' => [
                        'alert' => $alert,
                    ],
                ],
            ];

            foreach ($GLOBALS['_BAIDU_PUSH_APPS'] as $config) {

                $pusher = new Com_Push_BaiduPush($config['api_key'], $config['secret_key']);
                $deviceType = strtolower($config['device']);

                $pusher->pushMsgToAll($msg[$deviceType], $opts[$deviceType]);
            }

            return true;
        }
    }
}