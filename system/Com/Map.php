<?php

/**
 * 地图、坐标
 * 通用的助手函数集合
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_Map
{
    // 火星坐标系：
    // GCJ-02，国测局02年发布的坐标体系。又称“火星坐标”。在中国，必须至少使用GCJ-02的坐标体系。
    // 比如谷歌，腾讯，高德都在用这个坐标体系。他们三家都是通用的。GCJ-02也是国内最广泛使用的坐标体系。
    // 例外，百度API上取到的，是BD-09坐标，只适用于百度地图相关产品。
    // 例外，搜狗API上取到的，是搜狗坐标，只适用于搜狗地图相关产品。
    // 例外，谷歌地球上取到的，是GPS坐标，而且是度分秒形式的经纬度坐标。在国内不允许使用。必须转换为GCJ-02坐标。
    // @link http://lbsbbs.amap.com/forum.php?mod=viewthread&tid=74&extra=page%3D1

    // 火星坐标系 (GCJ-02) => 百度坐标系 (BD-09)
    public static function googleToBaidu($ggLon, $ggLat)
    {
        $X_PI = (3.14159265358979324 * 3000.0 / 180.0);

        $x = $ggLon;
        $y = $ggLat;
        $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $X_PI);
        $theta = atan2($y, $x) + 0.000003 * cos($x * $X_PI);

        $bdLon = $z * cos($theta) + 0.0065;
        $bdLat = $z * sin($theta) + 0.006;

        return [$bdLon, $bdLat];
    }

    // 百度坐标系 (BD-09) => 火星坐标系 (GCJ-02)
    public static function baiduToGoogle($bdLon, $bdLat)
    {
        $X_PI = (3.14159265358979324 * 3000.0 / 180.0);

        $x = $bdLon - 0.0065;
        $y = $bdLat - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $X_PI);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $X_PI);

        $ggLon = $z * cos($theta);
        $ggLat = $z * sin($theta);

        return [$ggLon, $ggLat];
    }

    /**
     * 计算两个坐标之间的距离（米）
     *
     * @param array $fromPoint 起点 [经度, 纬度]
     * @param array $destPoint 终点 [经度, 纬度]
     * @return int 距离（米）
     */
    public static function calcDistance(array $fromPoint, array $destPoint)
    {
        // 地球半径
        $fEARTH_RADIUS = 6378137;

        // 角度换算成弧度
        $fRadLng1 = deg2rad($fromPoint[0]);
        $fRadLng2 = deg2rad($destPoint[0]);
        $fRadLat1 = deg2rad($fromPoint[1]);
        $fRadLat2 = deg2rad($destPoint[1]);

        // 计算经纬度的差值
        $fD1 = abs($fRadLat1 - $fRadLat2);
        $fD2 = abs($fRadLng1 - $fRadLng2);

        // 距离计算
        $fP = pow(sin($fD1/2), 2) + cos($fRadLat1) * cos($fRadLat2) * pow(sin($fD2/2), 2);

        return intval($fEARTH_RADIUS * 2 * asin(sqrt($fP)) + 0.5);
    }

    // 地址组合（精确到区）
    public static function combineAddress($province, $city, $district)
    {
        static $municipalities = [
            '上海市',
            '北京市',
            '重庆市',
            '天津市',
            '香港特别行政区',
            '澳门特别行政区',
        ];

        if (in_array($province, $municipalities)) {
            return $city . $district;
        }
        else {
            return $province . $city . $district;
        }
    }
}