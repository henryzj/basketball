<?php

/**
 * 百度LBS云
 * Web服务API - Geoconv API 坐标转换
 * 坐标转换服务每日请求次数上限为100万次，每次最多支持100个坐标点的转换。
 *
 * @link http://lbsyun.baidu.com/index.php?title=webapi/guide/changeposition
 * @link http://lbsbbs.amap.com/forum.php?mod=viewthread&tid=74&extra=page%3D1
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_BaiduLBS_WebApi_Geoconv
{
    const API_URL = 'http://api.map.baidu.com/geoconv/v1/';

    /**
     * 将其他坐标系转化百度坐标系
     *
     * @param string $coords
     *        格式：经度,纬度;经度,纬度…
     *        限制：最多支持100个
     *        格式举例：
     *        114.21892734521,29.575429778924;114.21892734521,29.575429778924
     * @param int $from
     *        取值为如下：
     *        1：GPS设备获取的角度坐标，wgs84坐标;
     *        2：GPS获取的米制坐标、sogou地图所用坐标;
     *        3：google地图、soso地图、aliyun地图、mapabc地图和amap高德地图所用坐标=国测局GCJ-02坐标（又称“火星坐标系”）;
     *        4：3中列表地图坐标对应的米制坐标;
     *        5：百度地图采用的经纬度坐标;
     *        6：百度地图采用的米制坐标;
     *        7：mapbar地图坐标;
     *        8：51地图坐标
     * @param int $to
     *        有两种可供选择：5、6。
     *        5：bd09ll(百度经纬度坐标),
     *        6：bd09mc(百度米制经纬度坐标);
     *
     * @return x:经度 y:纬度
     */
    public static function convToBaiduCoords($coords, $from = 3, $to = 5)
    {
        if (! in_array($from, [1, 2, 3, 4, 5, 6, 7, 8])) {
            throws('坐标来源不合法');
        }

        $params = [
            'coords' => $coords,
            'from'   => $from,
            'to'     => $to,
            'output' => 'json',
            'ak'     => BAIDU_LBS_AK,
        ];

        // 构造签名
        $sn = Com_BaiduLBS_Util::calcAKSN(BAIDU_LBS_SK, '/geoconv/v1/', $params);

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

    // 将其他坐标系转化为百度坐标系
    public static function transToBdll($coordType, &$longitude, &$latitude)
    {
        // 本来就是百度坐标，无需转换
        if (! $coordType || $coordType == 5) {
            return [$longitude, $latitude];
        }

        try {
            $result = self::convToBaiduCoords($longitude . ',' . $latitude, $coordType, 5);
            $longitude = $result[0]['x'];
            $latitude  = $result[0]['y'];
        }
        catch (Exception $e) {
            // do nothing
        }

        return [$longitude, $latitude];
    }
}