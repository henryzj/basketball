<?php

/**
 * 高德地图开放平台 - 云检索
 *
 * @link http://lbs.amap.com/yuntu/reference/cloudsearch/
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_AMap_CloudSearch
{
    const API_URL = 'http://yuntuapi.amap.com/datasearch/';

    // poi周边搜索
    public static function getNearby($geoTblName, array $location, array $conditions = [], $page = 1, $pageSize = 10)
    {
        $geoTblId = $GLOBALS['_AMAP_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = self::__buildParams($geoTblId, $conditions, $page, $pageSize);

        $params += [
            // 逗号分隔的经纬度。经纬度小数点后不得超过6位
            'center' => implode(',', $location),
            // 取值范围[0,50000]，单位：米。若超出取值范围按默认值3000
            'radius'   => isset($conditions['radius']) ? $conditions['radius'] : null,
        ];

        return self::__callApi('around', $params);
    }

    // poi本地搜索（城市、地区）
    public static function getLocal($geoTblName, array $conditions = [], $page = 1, $pageSize = 10)
    {
        $geoTblId = $GLOBALS['_AMAP_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = self::__buildParams($geoTblId, $conditions, $page, $pageSize);

        $params += [
            // 市或区名，如北京市，海淀区。缺省为全国
            'city' => isset($conditions['city']) ? $conditions['city'] : '全国',
        ];

        return self::__callApi('local', $params);
    }

    // poi矩形、多边形检索
    public static function getBound($geoTblName, array $conditions = [], $page = 1, $pageSize = 10)
    {
        $geoTblId = $GLOBALS['_AMAP_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = self::__buildParams($geoTblId, $conditions, $page, $pageSize);

        // 坐标节点
        $polygons = [];
        foreach ($conditions['bounds'] as $point) {
            $polygons[] = $point[0] . ',' . $point[1];
        }

        $params += [
            // 左下角和右上角的经纬度坐标点。2个点用;号分隔
            // 规则： 逗号分隔的一对经纬度代表一个坐标，用分号分割多个坐标；
            // 如果只传两个坐标则认为这两坐标为矩形的左下和右上点；
            // 多边形数据的起点和终点必须相同，保证图形闭合。
            'polygon' => implode(';', $polygons)
        ];

        return self::__callApi('polygon', $params);
    }

    // 单条poi详情检索
    public static function getDetail($geoTblName, $poiId)
    {
        $geoTblId = $GLOBALS['_AMAP_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = [
            'key'         => AMAP_API_KEY,
            'geotable_id' => $geoTblId,
            '_id'         => $poiId,
        ];

        return self::__callApi('id', $params);
    }

    // 按条件检索数据（可遍历整表数据）
    public static function getList($geoTblName, array $conditions = [], $page = 1, $pageSize = 10)
    {
        $geoTblId = $GLOBALS['_AMAP_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = self::__buildParams($geoTblId, $conditions, $page, $pageSize);

        return self::__callApi('data/list', $params);
    }

    // 构造基本参数
    private static function __buildParams($geoTblId, array $conditions, $page, $pageSize)
    {
        $params = [
            'key'      => AMAP_API_KEY,
            'tableid'  => $geoTblId,
            'keywords' => isset($conditions['keywords']) ? implode('|', (array) $conditions['keywords']) : null,
            'sortrule' => isset($conditions['sortrule']) ? implode('+', (array) $conditions['sortrule']) : null,
            'filter'   => isset($conditions['filter'])   ? implode('+', (array) $conditions['filter'])   : null,
            'page'     => $page,     // 索引从1开始
            'limit'    => $pageSize,
        ];

        return $params;
    }

    private static function __callApi($method, array $params)
    {
        // 过滤空参数
        $params = array_filter($params, function ($param) {
            return $param !== null ? true : false;
        });

        // 构造签名
        $params['sig'] = Com_AMap_Util::buildSign($params, AMAP_API_SECRET);

        $url = self::API_URL . $method . '?' . http_build_query($params);

        $result = file_get_contents($url);
        $result = json_decode($result, true);

        if (! $result || ! isset($result['status'])) {
            throws('AMAP检索失败');
        }

        if ($result['status'] != 1) {
            throws('AMAP检索异常：' . $result['info']);
        }

        return $result;
    }
}