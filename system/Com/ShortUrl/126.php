<?php

/**
 * 网易短链接
 *
 * @author zhengjiang
 */

class Com_ShortUrl_126
{
    const APP_KEY = '4f0c04771d4e40b4945afcfdc0337e3d';

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
            $baseurl = 'http://126.am/api!shorten.action';
            $params = [
                'key'      => self::APP_KEY,
                'longUrl'  => $url,
            ];
        } else {
            $baseurl = 'http://126.am/api!expand.action';
            $params = [
                'key'       => self::APP_KEY,
                'shortUrl'  => $url,
            ];
        }

        $result = Com_Http::request($baseurl, $params, 'CURL-POST');
        $result = json_decode($result, true);

        if ($result['status_code'] != 200) {
            return false;
        }

        return $result['url'];
    }
}