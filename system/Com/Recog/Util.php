<?php

/**
 * 云扣人脸识别 - 助手函数
 *
 * @link http://www.recog.cn/
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_Recog_Util
{
    public static function buildSign(array $params, $apiSecret)
    {
        ksort($params);

        $string = '';

        foreach ($params as $key => $value) {
            $string .= $key . $value;
        }

        return strtoupper(sha1($string . $apiSecret));
    }

    public static function request($url, array $params = [])
    {
        $params += [
            'apiKey' => RECOG_API_KEY,
        ];

        $params['apiSign'] = Com_Recog_Util::buildSign($params, RECOG_API_SECRET);

        $result = Com_Http::request($url, $params, 'CURL-POST');
        $result = json_decode($result, true);

        if (! $result || ! isset($result['resultCode'])) {
            throw new Exception('云扣接口连接失败');
        }

        if ($result['resultCode'] != 0) {
            throw new Exception('云扣分析失败：' . $result['message']);
        }

        return $result['data'];
    }
}