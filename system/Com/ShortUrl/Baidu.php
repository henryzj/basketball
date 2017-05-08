<?php

/**
 * 百度短链接
 *
 * @author zhengjiang
 */

class Com_ShortUrl_Baidu
{
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
            $baseurl = 'http://dwz.cn/create.php';
            $params = [
                'url' => $url,
            ];
        } else {
            $baseurl = 'http://dwz.cn/query.php';
            $params = [
                'tinyurl' => $url,
            ];
        }

        $result = Com_Http::request($baseurl, $params, 'CURL-POST');
        $result = json_decode($result, true);

        if ($result['status'] != 0) {
            return false;
        }

        if ($type == 1) {
            return $result['tinyurl'];
        } else {
            return $result['longurl'];
        }
    }
}