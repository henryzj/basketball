<?php

/**
 * 高德地图开放平台 助手函数
 *
 * @link http://lbs.amap.com/
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_AMap_Util
{
    public static function buildSign(array $params, $apiSecret)
    {
        ksort($params);

        $strs = [];

        foreach ($params as $key => $value) {
            $strs[] = $key . '=' .  $value;
        }

        // 拼接待签名字符串
        $string = implode('&', $strs);

        return md5($string . $apiSecret);
    }
}