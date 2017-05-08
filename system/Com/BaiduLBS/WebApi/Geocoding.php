<?php

/**
 * 百度LBS云
 * Web服务API - Geocoding API
 *
 * @link http://developer.baidu.com/map/index.php?title=webapi/guide/webservice-geocoding
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_BaiduLBS_WebApi_Geocoding
{
    const API_URL = 'http://api.map.baidu.com/geocoder/v2/';

    // 将地址转换为经纬度
    public static function addrToLoc($address)
    {
        $params = [
            'address' => $address,
            'output'  => 'json',
            'ak'      => BAIDU_LBS_AK,
        ];

        // 构造签名
        $sn = Com_BaiduLBS_Util::calcAKSN(BAIDU_LBS_SK, '/geocoder/v2/', $params);

        $params['sn'] = $sn;
        $url = self::API_URL . '?' . http_build_query($params);

        $result = file_get_contents($url);
        $result = json_decode($result, true);

        if (! $result || ! isset($result['status'])) {
            throws('地址转换异常');
        }

        if ($result['status'] != 0) {
            throws('地址转换异常：' . (isset($result['msg']) ? $result['msg'] : $result['message']) . ' (' . $result['status'] . ')');
        }

        return $result['result'];
    }

    /**
     * 将经纬度转换为地址
     *
     * @param decimal $lng
     * @param decimal $lat
     * @param string $coordtype 坐标的类型，目前支持的坐标类型包括：bd09ll（百度经纬度坐标）、gcj02ll（国测局经纬度坐标）、wgs84ll（ GPS经纬度）
     * @param int $pois 是否显示周边POI
     * @return array
     */
    public static function locToAddr($lng, $lat, $coordtype = 'bd09ll', $pois = 0)
    {
        $params = [
            'coordtype' => $coordtype,
            'output'    => 'json',
            'ak'        => BAIDU_LBS_AK,
            'location'  => $lat . ',' . $lng,   // lat<纬度>,lng<经度>
            'pois'      => $pois,
        ];

        // 构造签名
        $sn = Com_BaiduLBS_Util::calcAKSN(BAIDU_LBS_SK, '/geocoder/v2/', $params);

        $params['sn'] = $sn;
        $url = self::API_URL . '?' . http_build_query($params);

        $result = file_get_contents($url);
        $result = json_decode($result, true);

        if (! $result || ! isset($result['status'])) {
            throws('坐标转换异常');
        }

        if ($result['status'] != 0) {
            throws('坐标转换异常：' . (isset($result['msg']) ? $result['msg'] : $result['message']) . ' (' . $result['status'] . ')');
        }

        return $result['result'];
    }

    // 将经纬度转换为地址（精确到区）
    public static function locToCity($lng, $lat, $coordtype = 'bd09ll', $pois = 0)
    {
        $geoInfo = self::locToAddr($lng, $lat, $coordtype, $pois);

        return Com_Map::combineAddress($geoInfo['addressComponent']['province'], $geoInfo['addressComponent']['city'], $geoInfo['addressComponent']['district']);
    }
}