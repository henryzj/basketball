<?php

/**
 * 融云IM
 * 核心方法集合
 *
 * @author JiangJian <silverd@sohu.com>
 */

if (! defined('RONGYUN_IM_APP_KEY')) {
    Yaf_Loader::import(CONF_PATH . 'cloud.php');
}

Yaf_Loader::import(SYS_PATH . 'Third/RongYun/ServerAPI.php');

class Com_RongYun
{
    protected static $_rong = null;

    public static function getRong()
    {
        if (self::$_rong === null) {
            self::$_rong = new ServerAPI(RONGYUN_IM_APP_KEY, RONGYUN_IM_APP_SECRET);
        }

        return self::$_rong;
    }

    public static function __callStatic($method, $arguments)
    {
        @ob_start();

        $result = call_user_func_array([self::getRong(), $method], $arguments);

        // 奇葩的融云API
        // 错误和异常竟然是输出到屏幕
        if ($output = ob_get_contents()) {
            throws('融云接口异常：' . $output);
        }

        $result = json_decode($result, true);

        if (! $result) {
            throws('融云接口繁忙，请稍后再试');
        }

        if ($result['code'] != 200) {
            throws('融云接口请求失败：' . $method . '，代码：' . $result['code']);
        }

        return $result;
    }

    public static function getUserToken($userId, $userName, $portraitUri)
    {
        $result = self::__callStatic('getToken', [$userId, $userName, $portraitUri]);

        return $result;
    }
}