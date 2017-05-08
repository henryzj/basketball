<?php

/**
 * 谷歌地图API - Geocoding
 *
 * @link https://developers.google.com/maps/web-services/
 * @link https://developers.google.com/maps/documentation/geocoding
 * @link https://developers.google.com/maps/faq#languagesupport 多语言选项
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_GoogleMaps_Geocoding
{
    // 中国大陆专用域名（非https）
    const API_URL_CN = 'http://maps.google.cn/maps/api/geocode/json';

    // 国际通用域名
    const API_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    // 正向解析：将地址转换为经纬度
    public static function addrToLoc($address, $language = 'zh-CN')
    {
        $params = [
            'address'  => $address,
            'language' => $language,
            'key'      => GOOGLE_MAPS_API_KEY,
        ];

        $apiUri = self::API_URL;

        // 国内API不需要密钥
        if ($language == 'zh-CN') {
            unset($params['key']);
            $apiUri = self::API_URL_CN;
        }

        $url = $apiUri . '?' . http_build_query($params);

        $result = file_get_contents($url);
        $result = json_decode($result, true);

        if (! $result || ! isset($result['status'])) {
            throws('地址转换异常');
        }

        if ($result['status'] != 'OK') {
            throws('地址转换异常：' . $result['status']);
        }

        return $result['results'][0]['geometry'];
    }

    // 反向解析：将经纬度转换为地址
    public static function locToAddr($lng, $lat, $language = 'zh-CN')
    {
        $params = [
            'latlng'   => $lat . ',' . $lng,   // 纬度,经度
            'language' => $language,
            'key'      => GOOGLE_MAPS_API_KEY,
        ];

        $apiUri = self::API_URL;

        // 国内API不需要密钥
        if ($language == 'zh-CN') {
            unset($params['key']);
            $apiUri = self::API_URL_CN;
        }

        $url = $apiUri . '?' . http_build_query($params);

        $result = file_get_contents($url);
        $result = json_decode($result, true);

        if (! $result || ! isset($result['status'])) {
            throws('坐标转换异常');
        }

        if ($result['status'] != 'OK') {
            throws('坐标转换异常：' . $result['status']);
        }

        return $result['results'];
    }
}