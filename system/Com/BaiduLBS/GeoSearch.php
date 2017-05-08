<?php

/**
 * 百度LBS云 - 云检索
 *
 * @link http://developer.baidu.com/map/wiki/index.php?title=lbscloud/api/geosearch
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_BaiduLBS_GeoSearch
{
    const API_URL = 'http://api.map.baidu.com/geosearch/v3/';

    // poi周边搜索
    // @link http://developer.baidu.com/map/wiki/index.php?title=lbscloud/api/geosearch#poi.E5.91.A8.E8.BE.B9.E6.90.9C.E7.B4.A2
    public static function getNearby($geoTblName, array $location, array $conditions = [], $page = 1, $pageSize = 10)
    {
        $geoTblId = $GLOBALS['_BAIDU_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = self::__buildParams($geoTblId, $conditions, $page, $pageSize);

        $params += [
            // 逗号分隔的经纬度
            'location' => implode(',', $location),
            // 单位为米，默认为1000
            'radius'   => isset($conditions['radius']) ? $conditions['radius'] : null,
        ];

        return self::__callApi('nearby', $params);
    }

    // poi本地搜索（城市、地区）
    public static function getLocal($geoTblName, array $conditions = [], $page = 1, $pageSize = 10)
    {
        $geoTblId = $GLOBALS['_BAIDU_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = self::__buildParams($geoTblId, $conditions, $page, $pageSize);

        $params += [
            // 市或区名，如北京市，海淀区。缺省为全国
            'region' => isset($conditions['region']) ? $conditions['region'] : null,
        ];

        return self::__callApi('local', $params);
    }

    // poi矩形检索
    public static function getBound($geoTblName, array $conditions = [], $page = 1, $pageSize = 10)
    {
        $geoTblId = $GLOBALS['_BAIDU_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = self::__buildParams($geoTblId, $conditions, $page, $pageSize);

        $params += [
            // 左下角和右上角的经纬度坐标点。2个点用;号分隔
            'bounds' => $conditions['bounds'][0][0] . ',' . $conditions['bounds'][0][1] . ';' . $conditions['bounds'][1][0] . ',' . $conditions['bounds'][1][1]
        ];

        return self::__callApi('bound', $params);
    }

    // 单条poi详情检索
    public static function getDetail($geoTblName, $poiUid)
    {
        $geoTblId = $GLOBALS['_BAIDU_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        return self::__callApi('detail/' . $poiUid, [
            'ak'          => BAIDU_LBS_AK,
            'geotable_id' => $geoTblId,
            'coord_type'  => 3
        ]);
    }

    // 构造基本参数
    private static function __buildParams($geoTblId, array $conditions, $page, $pageSize)
    {
        $params = [
            'ak'          => BAIDU_LBS_AK,
            'geotable_id' => $geoTblId,
            'coord_type'  => 3,
            'q'           => isset($conditions['q']) ? $conditions['q'] : null,
            'tags'        => isset($conditions['tags'])   ? implode(' ', (array) $conditions['tags']) : null,
            'sortby'      => isset($conditions['sortby']) ? implode('|', (array) $conditions['sortby']) : null,
            'filter'      => isset($conditions['filter']) ? implode('|', (array) $conditions['filter']) : null,
            'page_index'  => $page - 1,     // 索引从0开始
            'page_size'   => $pageSize,
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
        $params['sn'] = Com_BaiduLBS_Util::calcAKSN(BAIDU_LBS_SK, '/geosearch/v3/' . $method, $params);

        $url = self::API_URL . $method . '?' . http_build_query($params);

        $result = file_get_contents($url);
        $result = json_decode($result, true);

        if (! $result || ! isset($result['status'])) {
            throws('POI检索失败');
        }

        if ($result['status'] != 0) {
            throws('POI检索异常：' . (isset($result['msg']) ? $result['msg'] : $result['message']) . ' (' . $result['status'] . ')');
        }

        return $result;
    }
}