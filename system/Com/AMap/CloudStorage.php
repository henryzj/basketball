<?php

/**
 * 高德地图开放平台 - 云存储
 *
 * @link 接口文档 http://lbs.amap.com/yuntu/reference/cloudstorage/
 * @link 管理后台 http://yuntu.amap.com/datamanager/
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_AMap_CloudStorage
{
    const API_URL = 'http://yuntuapi.amap.com/datamanage/';

    public static function createTable($tblName)
    {
        $params = [
            'name' => $tblName
        ];

        $result = self::__callApi('POST', 'table/create', $params);

        return $result['tableid'];
    }

    // 创建数据（create poi）接口
    public static function createPoi($geoTblName, array $postData)
    {
        $geoTblId = $GLOBALS['_AMAP_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = [
            'tableid' => $geoTblId,
            'loctype' => 1,
            'data'    => [
                'coordtype' => 'autonavi',  // 1:gps 2:autonavi 3:baidu
                '_name'     => $postData['name'],
                '_location' => $postData['longitude'] . ',' . $postData['latitude'],
                '_address'  => $postData['address'],
            ],
        ];

        // 自定义字段
        if (isset($postData['custom_cols'])) {
            $params['data'] += $postData['custom_cols'];
        }

        $params['data'] = json_encode($params['data'], JSON_UNESCAPED_UNICODE);

        $result = self::__callApi('POST', 'data/create', $params);

        return $result['_id'];
    }

    public static function batchCreatePois($geoTblName, $csvFileStream)
    {
        // TODO
    }

    // 修改数据（poi）接口
    public static function updatePoi($geoTblName, array $postData, $pk)
    {
        $geoTblId = $GLOBALS['_AMAP_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = [
            'tableid' => $geoTblId,
            'loctype' => 1,
            'data'    => [
                'coordtype' => 'autonavi',  // 1:gps 2:autonavi 3:baidu
                '_id'       => $postData['poi_id'], // WHERE 条件
                '_name'     => $postData['name'],
                '_location' => $postData['longitude'] . ',' . $postData['latitude'],
                '_address'  => $postData['address'],
            ],
        ];

        // 自定义字段
        if (isset($postData['custom_cols'])) {
            $params['data'] += $postData['custom_cols'];
        }

        $params['data'] = json_encode($params['data'], JSON_UNESCAPED_UNICODE);

        $result = self::__callApi('POST', 'data/update', $params);

        return $result['status'];
    }

    // 删除数据（poi）接口
    public static function deletePoi($geoTblName, $poiIds)
    {
        $geoTblId = $GLOBALS['_AMAP_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = [
            'tableid' => $geoTblId,
            'ids'     => implode(',', (array) $poiIds),
        ];

        $result = self::__callApi('POST', 'data/delete', $params);

        return $result['status'];
    }

    private static function __callApi($reqMethod, $apiMethod, array $params)
    {
        // 过滤空参数
        $params = array_filter($params, function ($param) {
            return $param !== null ? true : false;
        });

        $params['key'] = AMAP_API_KEY;

        // 构造签名
        $params['sig'] = Com_AMap_Util::buildSign($params, AMAP_API_SECRET);

        $result = Com_Http::request(self::API_URL . $apiMethod, $params, 'CURL-' . $reqMethod);
        $result = json_decode($result, true);

        if (! $result || ! isset($result['status'])) {
            throws('AMAP云存储失败');
        }

        if ($result['status'] != 1) {
            throws('AMAP云存储异常：' . $result['info']);
        }

        return $result;
    }
}