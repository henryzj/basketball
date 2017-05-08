<?php

/**
 * 微博短链接
 *
 * @author zhengjiang
 */

class Com_ShortUrl_Weibo
{
    const APP_KEY = '4035291851';

    /**
     * 将长短地址互相转换
     *
     * @param string $url 源地址
     * @param int type 0:短转长 1:长转短
     * @return string
     */
    public static function convert($url, $type = 1)
    {
        if ($type == 1) {
            $baseurl = 'http://api.t.sina.com.cn/short_url/shorten.json';
            $params = [
                'source'   => self::APP_KEY,
                'url_long' => $url,
            ];
        } else {
            $baseurl = 'http://api.t.sina.com.cn/short_url/expand.json';
            $params = [
                'source'    => self::APP_KEY,
                'url_short' => $url,
            ];
        }

        $result = Com_Http::request($baseurl, $params, 'CURL-POST');
        $result = json_decode($result, true);

        if (isset($result['error']) || ! isset($result[0]['url_long']) || ! $result[0]['url_long']) {
            throw new Exception($result['error'], $result['error_code']);
        }

        if ($type == 1) {
            return $result[0]['url_short'];
        } else {
            return $result[0]['url_long'];
        }
    }
}