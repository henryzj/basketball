<?php

/**
 * 微软牛津计划 - 助手函数
 *
 * @link https://www.projectoxford.ai/doc/
 *
 * @author JiangJian <silverd29@hotmail.com>
 */

class Com_Oxford_Util
{
    public static function request($url, $params, $contentType = 'application/json')
    {
        $headers = [
            'Content-Type: ' . $contentType,
            'Ocp-Apim-Subscription-Key: ' . OXFORD_FACE_API_KEY1,
        ];

        $result = self::_http('POST-RAW', $url, $params, $headers);
        $result = json_decode($result, true);

        if (! $result) {
            throw new Exception('网络繁忙，请稍后再试');
        }

        if (isset($result['error'])) {
            throw new Exception('分析失败：' . $result['error']['code'] . ' - ' . $result['error']['message']);
        }

        return $result;
    }

    protected static function _http($method, $url, $params, array $headers = [])
    {
        $ch = curl_init();

        if (! empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $method = strtoupper($method);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        elseif ($method == 'POST-RAW') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $result = curl_exec($ch);

        if ($errCode = curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        return $result;
    }
}