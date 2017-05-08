<?php

/**
 * 模拟发送 GET/POST 等请求
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Http.php 7303 2013-12-11 01:27:58Z jiangjian $
 */

class Com_Http
{
    /**
     * 发送请求（对外公开的入口）
     *
     * @param string $url
     * @param array $data
     * @param string $via CURL-POST | CURL-GET | GET | SOCKET-POST | SOCKET-GET
     * @param int $timeOut
     * @return mixed
     */
    public static function request($url, $data = array(), $via = 'GET', $isHttps = false, $timeOut = 0)
    {
        if ($via == 'CURL-POST') {
            return self::_curl($url, $data, 'POST', $isHttps, $timeOut);
        } elseif ($via == 'CURL-GET') {
            return self::_curl($url, $data, 'GET', $isHttps, $timeOut);
        } elseif ($via == 'GET') {
            return self::_get($url, $data, $isHttps, $timeOut);
        } elseif ($via == 'SOCKET-POST') {
            return self::_socket($url, $data, 'POST', $isHttps, $timeOut);
        } elseif ($via == 'SOCKET-GET') {
            return self::_socket($url, $data, 'GET', $isHttps, $timeOut);
        }
    }

    /**
     * 将数组构造成参数串 URL
     *
     * @param string $url
     * @param array $data
     * @return string
     */
    private static function _buildUrl($url, $data)
    {
        if ($data) {
            $data = is_array($data) ? $data : array($data);
            $params = http_build_query($data);
            $url = $url . (strpos($url, '?') === false ? '?' : '&') . $params;
        }

        return $url;
    }

    /**
     * 最简单的 GET 请求
     *
     * @param string $url
     * @param array $data
     * @param int $timeOut
     * @return bool
     */
    private static function _get($url, $data = array(), $isHttps = false, $timeOut = 0)
    {
        $url = self::_buildUrl($url, $data);
        return self::_fileGetContents($url, $timeOut);
    }

    /**
     * CURL 请求
     *
     * @param string $url
     * @param array $data
     * @param string $method POST | GET
     * @param int $timeOut
     * @return bool
     */
    private static function _curl($url, $data = array(), $method = 'POST', $isHttps = false, $timeOut = 0)
    {
        $ch = curl_init();

        if ($method == 'POST') {
            $data = is_array($data) ? http_build_query($data) : $data;
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            $url = self::_buildUrl($url, $data);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($timeOut) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeOut);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        }

        // https 处理
        // 方式1：不验证证书
        if ($isHttps === true) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        // 方式2：指定SSL证书
        elseif ($isHttps) {
            $caPaths = $isHttps;
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // SSL证书认证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    // 严格认证
            curl_setopt($ch, CURLOPT_SSLCERT, $caPaths['ssl_cert']);
            curl_setopt($ch, CURLOPT_SSLKEY, $caPaths['ssl_key']);
            curl_setopt($ch, CURLOPT_CAINFO, $caPaths['ca_info']);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * CURL 请求
     *
     * @param string $url
     * @param array $data
     * @param string $method POST | GET
     * @param int $timeOut
     * @return bool
     */
    private static function _socket($url, $data = array(), $method = 'POST', $isHttps = false, $timeOut = 0)
    {
        $urlParts = parse_url($url);

        $host    = isset($urlParts['host'])  ? $urlParts['host']  : '';
        $port    = isset($urlParts['port'])  ? $urlParts['port']  : '';
        $path    = isset($urlParts['path']) && $urlParts['path'] ? $urlParts['path']  : '/';
        $query   = isset($urlParts['query']) ? $urlParts['query'] : '';
        $request = $path  . '?' . $query;

        $fsock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (! $fsock) {
            return -1;
        }

        socket_set_nonblock($fsock);
        @socket_connect($fsock, $host, $port);
        $ret = socket_select($fd_read = array($fsock), $fd_write = array($fsock), $except = null, $timeOut, 0);
        if ($ret != 1) {
            @socket_close($fsock);
            return -2;
        }

        $in = $method .' ' . $request . " HTTP/1.0\r\n";
        $in .= "Accept: */*\r\n";
        $in .= "User-Agent: Mozilla/5.0\r\n";
        $in .= 'Host: ' . $host . "\r\n";
        if ($method == 'POST') {
            $postData = is_array($data) ? http_build_query($data) : $data;
            $in .= "Content-type: application/x-www-form-urlencoded\r\n";
            $in .= 'Content-Length: ' . strlen($postData) . "\r\n";
        }
        $in .= "Connection: Close\r\n\r\n";
        if ($method == 'POST') {
            $in .= $postData . "\r\n\r\n";
        }
        unset($postData);

        if (! @socket_write($fsock, $in, strlen($in))) {
            socket_close($fsock);
            return -4;
        }
        unset($in);

        socket_set_block($fsock);
        @socket_set_option($fsock, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeOut, 'usec' => 0));

        $out = '';
        while ($buff = socket_read($fsock, 2048)){
            $out .= $buff;
        }

        @socket_close($fsock);

        if (! $out) {
            return -5;
        }

        $pos = strpos($out, "\r\n\r\n");
        $body = substr($out, $pos + 4);

        return trim($body);
    }

    /**
     * 有超时的 file_get_contents 封装
     *
     * @author Lujun <jun.lu.726@gmail.com>
     *
     * @param string $url
     * @param int $timeOut
     * @return string
     */
    private static function _fileGetContents($url, $timeOut = 0)
    {
        if (empty($url)) {
            return false;
        }

        if (! $timeOut) {
            return file_get_contents($url);
        }

        $ctx = stream_context_create(array(
            'http' => array(
                'timeout' => $timeOut // 设置一个超时时间，单位为秒
            )
        ));

        return file_get_contents($url, 0, $ctx);
    }

    // 发起CURL请求
    public static function advRequest($method, $url, $params = [], array $headers = [], array &$cookies = [], $isDebug = false)
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
            curl_setopt($ch, CURLOPT_POSTFIELDS, rawurlencode($params));
        }
        elseif ($method == 'GET') {
            $url = self::_buildUrl($url, $params);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate,sdch');

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // 设定HTTP请求中"Cookie: "部分的内容。
        // 多个cookie用分号分隔，分号后带一个空格
        // 例如：fruit=apple; colour=red
        if ($cookies) {
            curl_setopt($ch, CURLOPT_COOKIE, implode(';', $cookies));
        }

        // 设置curl默认访问为IPv4
        // 如果开启了IPv6，curl默认会优先解析 IPv6，在对应域名没有 IPv6 的情况下
        // 会等待 IPv6 dns解析失败 timeout 之后才按以前的正常流程去找 IPv4，这样会导致超时
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }

        $response = curl_exec($ch);

        // 调试断点输出
        if ($isDebug) {
            echo '<pre>';
            var_dump($url);
            var_dump($params);
            var_dump($headers);
            var_dump($cookies);
            var_dump($response);
            echo '</pre>';
            exit();
        }

        $result = [
            'header' => '',
            'body' => '',
        ];

        // 响应状态码
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (in_array($httpStatusCode, [200, 401])) {

            // 拆分响应header和body
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $result['header'] = substr($response, 0, $headerSize);
            $result['body'] = substr($response, $headerSize);

            // 从响应header中抽出cookie
            preg_match_all('/Set\-Cookie:([^\r\n]*)/i', $result['header'], $matches);
            $cookies = array_unique(array_merge($cookies, $matches[1]));
        }

        else {

            if ($errMsg = curl_error($ch)) {

                // 写错误日志
                Com_Logger_Redis::error('curlHttp', $errMsg);

                // 调试模式直接输出
                if (isDebug()) {
                    throw new Exception($errMsg, curl_errno($ch));
                }
            }

            throw new Exception('网络繁忙，请稍候再试', -$httpStatusCode);
        }

        curl_close($ch);

        return $result;
    }
}