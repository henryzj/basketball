<?php

/**
 * 百度LBS云 助手函数
 *
 * @link http://developer.baidu.com/map/
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_BaiduLBS_Util
{
    public static function calcAKSN($sk, $url, array $params, $method = 'GET')
    {
        if ($method === 'POST') {
            ksort($params);
        }

        return md5(urlencode($url . '?' . http_build_query($params) . $sk));
    }
}