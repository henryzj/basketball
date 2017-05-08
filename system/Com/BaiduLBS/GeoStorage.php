<?php

/**
 * 百度LBS云 - 云存储
 *
 * @link 接口文档 http://developer.baidu.com/map/wiki/index.php?title=lbscloud/api/geodata
 * @link 管理后台 http://lbsyun.baidu.com/datamanager/datamanage
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_BaiduLBS_GeoStorage
{
    const API_URL = 'http://api.map.baidu.com/geodata/v3/';

    // 创建数据（create poi）接口
    public static function createPoi($geoTblName, array $postData)
    {
        $geoTblId = $GLOBALS['_BAIDU_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = [
            // 以下必填
            'ak'          => BAIDU_LBS_AK,
            'coord_type'  => 3,
            'geotable_id' => $geoTblId,
            'latitude'    => $postData['latitude'],
            'longitude'   => $postData['longitude'],
            // 以下选填
            'title'       => isset($postData['title'])   ? $postData['title']   : null,
            'address'     => isset($postData['address']) ? $postData['address'] : null,
            'tags'        => isset($postData['tags'])    ? $postData['tags']    : null,
        ];

        // 自定义字段
        if (isset($postData['custom_cols'])) {
            $params += $postData['custom_cols'];
        }

        return self::__callApi('POST', 'poi/create', $params);
    }

    // 修改数据（poi）接口
    public static function updatePoi($geoTblName, array $postData, $pk)
    {
        $geoTbl = $GLOBALS['_BAIDU_LBS_GEO_TBLS'][$geoTblName];

        $params = [
            // 以下必填
            'ak'          => BAIDU_LBS_AK,
            'coord_type'  => 3,
            'geotable_id' => $geoTbl['tbl_id'],
            // 以下选填
            'latitude'    => isset($postData['latitude'])  ? $postData['latitude']  : null,
            'longitude'   => isset($postData['longitude']) ? $postData['longitude'] : null,
            'title'       => isset($postData['title'])     ? $postData['title']     : null,
            'address'     => isset($postData['address'])   ? $postData['address']   : null,
            'tags'        => isset($postData['tags'])      ? $postData['tags']      : null,
        ];

        // 唯一主键（WHERE条件）
        $params[$geoTbl['pk']] = $pk;

        // 自定义字段
        if (isset($postData['custom_cols'])) {
            $params += $postData['custom_cols'];
        }

        return self::__callApi('POST', 'poi/update', $params);
    }

    // 修改数据（poi）接口（不存在则新增）
    public static function touchPoi($geoTblName, array $postData, $pk)
    {
        if (! self::detailPoi($geoTblName, $pk)) {
            return self::createPoi($geoTblName, $postData);
        }
        else {
            return self::updatePoi($geoTblName, $postData, $pk);
        }
    }

    // 删除数据（poi）接口
    public static function deletePoi($geoTblName, $pk)
    {
        $geoTbl = $GLOBALS['_BAIDU_LBS_GEO_TBLS'][$geoTblName];

        $params = [
            // 以下必填
            'ak'          => BAIDU_LBS_AK,
            'geotable_id' => $geoTbl['tbl_id'],
        ];

        // 唯一主键（WHERE条件）
        $params[$geoTbl['pk']] = $pk;

        return self::__callApi('POST', 'poi/delete', $params);
    }

    // 查询指定id的数据（poi）详情接口
    public static function detailPoi($geoTblName, $pk)
    {
        $geoTbl = $GLOBALS['_BAIDU_LBS_GEO_TBLS'][$geoTblName];

        $params = [
            // 以下必填
            'ak'          => BAIDU_LBS_AK,
            'geotable_id' => $geoTbl['tbl_id'],
        ];

        // 唯一主键（WHERE条件）
        $params[$geoTbl['pk']] = $pk;

        $result = self::__callApi('GET', 'poi/detail', $params);

        return isset($result['poi']) ? $result['poi'] : null;
    }

    // 查询指定条件的数据（poi）列表接口
    public static function listPoi()
    {

    }

    // 批量上传数据（post pois csv file）接口
    public static function multiUploadPoi()
    {

    }

    // 创建列（create column）接口
    public static function createCol($geoTblName, array $postData)
    {
        $geoTblId = $GLOBALS['_BAIDU_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = [
            // 以下必填
            'ak'          => BAIDU_LBS_AK,
            'coord_type'  => 3,
            'geotable_id' => $geoTblId,
            'key'         => $postData['key'],      // 字段名（英文）
            'name'        => $postData['name'],     // 字段注释（中文）
            'type'        => $postData['type'],     // 枚举值 1:Int64, 2:double, 3:string, 4:在线图片url
            // 以下选填
            'default_value'       => isset($postData['default_value'])       ? $postData['default_value']       : null,
            'max_length'          => isset($postData['max_length'])          ? $postData['max_length']          : null,
            'is_sortfilter_field' => isset($postData['is_sortfilter_field']) ? $postData['is_sortfilter_field'] : null,
            'is_search_field'     => isset($postData['is_search_field'])     ? $postData['is_search_field']     : null,
            'is_index_field'      => isset($postData['is_index_field'])      ? $postData['is_index_field']      : null,
            'is_unique_field'     => isset($postData['is_unique_field'])     ? $postData['is_unique_field']     : null,
        ];

        return self::__callApi('POST', 'column/create', $params);
    }

    // 修改指定条件列（column）接口
    public static function updateCol($geoTblName, $colId, array $postData)
    {
        $geoTblId = $GLOBALS['_BAIDU_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = [
            // 以下必填
            'ak'          => BAIDU_LBS_AK,
            'coord_type'  => 3,
            'geotable_id' => $geoTblId,
            'id'          => $colId,
            // 以下选填
            'name'                => isset($postData['name'])                ? $postData['name']                : null,
            'default_value'       => isset($postData['default_value'])       ? $postData['default_value']       : null,
            'max_length'          => isset($postData['max_length'])          ? $postData['max_length']          : null,
            'is_sortfilter_field' => isset($postData['is_sortfilter_field']) ? $postData['is_sortfilter_field'] : null,
            'is_search_field'     => isset($postData['is_search_field'])     ? $postData['is_search_field']     : null,
            'is_index_field'      => isset($postData['is_index_field'])      ? $postData['is_index_field']      : null,
            'is_unique_field'     => isset($postData['is_unique_field'])     ? $postData['is_unique_field']     : null,
        ];

        return self::__callApi('POST', 'column/update', $params);
    }

    // 创建表（create geotable）接口
    public static function createTable(array $postData)
    {
        $params = [
            // 以下必填
            'ak'           => BAIDU_LBS_AK,
            'geotype'      => 1, // 1：点；2：线；3：面。默认为1（当前只支持点）
            'name'         => $postData['name'],              // 表名
            'is_published' => $postData['is_published'],      // 是否发布到检索
        ];

        return self::__callApi('POST', 'geotable/create', $params);
    }

    // 修改表（update geotable）接口
    public static function updateTable($geoTblId, array $postData)
    {
        $geoTblId = $GLOBALS['_BAIDU_LBS_GEO_TBLS'][$geoTblName]['tbl_id'];

        $params = [
            // 以下必填
            'ak'           => BAIDU_LBS_AK,
            'id'           => $geoTblId,
            // 以下选填
            'name'         => isset($postData['name']) ? $postData['name'] : null,
            'is_published' => isset($postData['is_published']) ? $postData['is_published'] : null,
        ];

        return self::__callApi('POST', 'geotable/update', $params);
    }

    private static function __callApi($reqMethod, $apiMethod, array $params)
    {
        // 过滤空参数
        $params = array_filter($params, function ($param) {
            return $param !== null ? true : false;
        });

        // 构造签名
        $params['sn'] = Com_BaiduLBS_Util::calcAKSN(BAIDU_LBS_SK, '/geodata/v3/' . $apiMethod, $params, $reqMethod);

        $result = Com_Http::request(self::API_URL . $apiMethod, $params, 'CURL-' . $reqMethod);
        $result = json_decode($result, true);

        if (! $result || ! isset($result['status'])) {
            throws('LBS云存储失败');
        }

        if ($result['status'] != 0) {
            throws('LBS云存储异常：' . (isset($result['msg']) ? $result['msg'] : $result['message']) . ' (' . $result['status'] . ')');
        }

        return $result;
    }
}