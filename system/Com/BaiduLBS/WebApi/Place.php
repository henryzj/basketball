<?php

/**
 * Web服务API - Place API
 *
 * @link http://lbsyun.baidu.com/index.php?title=webapi/guide/webservice-placeapi
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_BaiduLBS_WebApi_Place
{
    const API_URL = 'http://api.map.baidu.com/place/v2/';

    // 本地检索(默认全国)
    public static function searchLocal(array $conditions = [], $page, $pageSize)
    {
        $params = self::__buildParams($conditions, $page, $pageSize);

        // 设定检索区域(默认为全国)
        $params += [
            'region' => isset($conditions['region']) ? $conditions['region'] : '全国',
        ];

        return self::__callApi('search', $params);
    }

    // 圆形检索
    public static function searchNearBy(array $conditions, $page, $pageSize)
    {
        $params = self::__buildParams($conditions, $page, $pageSize);

        // 设定检索中心点,检索范围默认50公里
        $params += [
            'location' => $conditions['longitude'] . ',' . $conditions['latitude'],
            'radius'   => isset($conditions['radius']) ? $conditions['radius'] : 1000 * 50,
        ];

        return self::__callApi('search', $params);
    }

    // 矩形检索
    public static function searchBound(array $conditions, $page, $pageSize)
    {
        $params = self::__buildParams($conditions, $page, $pageSize);

        $params += [
            // 左下角和右上角的经纬度坐标点。2个点用;号分隔
            'bounds' => $conditions['bounds'][0][0] . ',' . $conditions['bounds'][0][1] . ';' . $conditions['bounds'][1][0] . ',' . $conditions['bounds'][1][1],
        ];

        return self::__callApi('search', $params);
    }

    // 匹配用户输入关键字辅助信息、提示
    public static function suggestion(array $conditions)
    {
        $params = [
            'q'        => isset($conditions['q']) ? $conditions['q']: null,
            'region'   => isset($conditions['region']) ? $conditions['region'] : '全国',
            'output'   => 'json',
            'ak'       => BAIDU_LBS_AK,
        ];

        if (isset($conditions['longitude']) && isset($conditions['latitude'])) {
            $params += [
                'location' => $conditions['latitude'] . ',' . $conditions['longitude'],
            ];
        }

        return self::__callApi('suggestion', $params);
    }

    // 构造基本参数
    private static function __buildParams(array $conditions, $page, $pageSize)
    {
        if (! isset($conditions['q']) || ! $conditions['q']) {
            throws('检索关键字不能为空');
        }

        $params = [
            'ak'         => BAIDU_LBS_AK,
            'coord_type' => 2,  // 火星坐标系
            'scope'      => isset($conditions['scope']) ?: 1,
            'q'          => implode('$', (array) $conditions['q']),
            'tag'        => isset($conditions['tags'])   ?: null,
            'filter'     => isset($conditions['filter']) ? implode('|', (array) $conditions['filter']) : null,
            'page_num'   => $page - 1,     // 索引从0开始
            'page_size'  => $pageSize,
            'output'     => 'json',
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
        $params['sn'] = Com_BaiduLBS_Util::calcAKSN(BAIDU_LBS_SK, '/place/v2/' . $method, $params);

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