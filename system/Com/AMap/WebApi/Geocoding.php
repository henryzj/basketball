<?php

/**
 * 高德地图开放平台
 * Web服务API - 地理/逆地理编码API
 *
 * @link http://lbs.amap.com/api/webservice/summary/
 * @link http://lbs.amap.com/api/webservice/reference/georegeo/
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_AMap_WebApi_Geocoding
{
    // 将地址转换为经纬度
    public static function addrToLoc($address)
    {
        $params = [
            'address' => $address,
            'output'  => 'json',
            'key'     => AMAP_API_KEY,
        ];

        // 构造签名
        $params['sig'] = Com_AMap_Util::buildSign($params, AMAP_API_SECRET);

        $url = 'http://restapi.amap.com/v3/geocode/geo?' . http_build_query($params);

        $result = file_get_contents($url);
        $result = json_decode($result, true);

        if (! $result || ! isset($result['status'])) {
            throws('地址转换异常');
        }

        if ($result['status'] != 1) {
            throws('地址转换异常：' . $result['info']);
        }

        return isset($result['geocodes'][0]) ? $result['geocodes'][0] : null;
    }

    /**
     * 将经纬度转换为地址
     *
     * @param decimal $lng 经度（小数点后不得超过6位）
     * @param decimal $lat 纬度（小数点后不得超过6位）
     * @return array
     */
    public static function locToAddr($lng, $lat)
    {
        $params = [
            'output'     => 'json',
            'key'        => AMAP_API_KEY,
            'location'   => round($lng, 6) . ',' . round($lat, 6),
            'extensions' => 'base',
        ];

        // 构造签名
        $params['sig'] = Com_AMap_Util::buildSign($params, AMAP_API_SECRET);

        $url = 'http://restapi.amap.com/v3/geocode/regeo?' . http_build_query($params);

        $result = file_get_contents($url);
        $result = json_decode($result, true);

        if (! $result || ! isset($result['status'])) {
            throws('坐标转换异常');
        }

        if ($result['status'] != 1) {
            throws('坐标转换异常：' . $result['info']);
        }

        return $result['regeocode'];
    }
}