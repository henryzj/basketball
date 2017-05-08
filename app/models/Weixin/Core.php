<?php

/**
 * 微信-核心函数集合
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_Core extends Core_Model_Abstract
{
    public static function curl($url, $params = null, $isNeedCert = false, $format = 'JSON')
    {
        // 不需要CA证书的普通HTTPS请求
        if (! $isNeedCert) {
            $result = Com_Http::request($url, $params, 'CURL-POST', true, WX_CURL_TIMEOUT);
        }
        // 部分请求需要CA证书（例如发红包）
        else {
            $result = Com_Http::request($url, $params, 'CURL-POST', [
                'ssl_cert' => WX_SSL_CERT_PATH,
                'ssl_key'  => WX_SSL_KEY_PATH,
                'ca_info'  => WX_CA_INFO_PATH,
            ], WX_CURL_TIMEOUT);
        }

        // 解析结果
        if ($format == 'XML') {
            $result = Helper_String::xmlToArray($result);
        }
        // 默认通信格式
        elseif ($format == 'JSON') {
            $result = json_decode($result, true);
        }
        // 例如：下载图片
        elseif ($format == 'STREAM') {
            // 如果返回不是二进制流，则表示有异常
            if (! Helper_String::isImgStream($result)) {
                $result = json_decode($result, true);
            }
        }

        if (! $result) {
            throw new Model_Weixin_Exception('微信接口繁忙，请稍后再试');
        }

        if (is_array($result) && isset($result['errcode']) && $result['errcode'] != 0) {
            throw new Model_Weixin_Exception('微信接口响应错误：' . $result['errcode'] . '-' . $result['errmsg'], $result['errcode']);
        }

        // 为了兼容“企业转账”API的响应格式
        if (is_array($result) && isset($result['err_code'])) {
            throw new Model_Weixin_Exception('微信接口响应错误：' . $result['err_code'] . '-' . $result['err_code_des']);
        }

        return $result;
    }

    public static function curlWithAccessToken($orgUrl, $params = null, $isNeedCert = false, $format = 'JSON')
    {
        // 已重试次数
        static $failTimes = 0;

        $accessToken = self::getAccessToken();

        $url = $orgUrl . (strpos($orgUrl, '?') === false ? '?' : '&') . 'access_token=' . $accessToken;

        try {

            // 向网关发起正式请求
            $result = self::curl($url, $params, $isNeedCert, $format);

            // 成功后清零重试次数
            $failTimes = 0;
        }

        catch (Model_Weixin_Exception $e) {

            // 如果是 AccessToken 异常，则重刷后自动重试
            if (in_array($e->getCode(), [40001, 40014, 42001])) {

                if ($failTimes < 3) {

                    // 累加已失败次数
                    $failTimes++;

                    // 强制重刷 AccessToken
                    self::getAccessToken(true);

                    // 重新再发起一次请求
                    return self::curlWithAccessToken($orgUrl, $params, $isNeedCert, $format);
                }

                // 清零重试次数
                $failTimes = 0;

                throw new Model_Weixin_Exception($e->getMessage() . ', 已重试' . $failTimes . '次');
            }

            // 其他类型异常则重新抛出，让外部继续捕捉
            throw $e;
        }

        return $result;
    }

    // 获取官方公众号全局AccessToken
    public static function getAccessToken($isForceRefresh = false)
    {
        if (! $isForceRefresh) {
            // 没有过期则直接返回
            if (C('WX_GLB_ACCESS_TOKEN_EXPIRES_AT') > $GLOBALS['_TIME']) {
                return C('WX_GLB_ACCESS_TOKEN');
            }
        }

        // 如果已过期则去微信再取一次
        // 注意：官方限制了每天最多调用2000次
        $params = [
            'appid'      => WX_MP_APP_ID,
            'secret'     => WX_MP_APP_SECRET,
            'grant_type' => 'client_credential',
        ];

        $url = 'https://api.weixin.qq.com/cgi-bin/token?' . http_build_query($params);
        $result = self::curl($url);

        if (! isset($result['access_token']) || ! $result['access_token']) {
            throw new Model_Weixin_Exception('获取全局 AccessToken 失败');
        }

        // 保存入库
        C('WX_GLB_ACCESS_TOKEN', $result['access_token']);

        // 保存过期时间点
        // 这里不用 $result['expires_in'] 的原因：防止我方服务器跟微信方有时间误差
        // 为保险起见，这里有效期固定写成1小时
        C('WX_GLB_ACCESS_TOKEN_EXPIRES_AT', $GLOBALS['_TIME'] + 3600);

        return $result['access_token'];
    }

    public static function getJsApiTicket()
    {
        // 没有过期则直接返回
        if (C('WX_GLB_JS_API_TICKET_EXPIRES_AT') > $GLOBALS['_TIME']) {
            return C('WX_GLB_JS_API_TICKET');
        }

        // 如果已过期则去微信再取一次
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi';
        $result = self::curlWithAccessToken($url);

        if (! isset($result['ticket']) || ! $result['ticket']) {
            throw new Model_Weixin_Exception('获取JsApiTicket失败');
        }

        // 保存入库
        C('WX_GLB_JS_API_TICKET', $result['ticket']);

        // 保存过期时间点
        // 这里不用 $result['expires_in'] 的原因：防止我方服务器跟微信方有时间误差
        // 为保险起见，这里有效期固定写成1小时
        C('WX_GLB_JS_API_TICKET_EXPIRES_AT', $GLOBALS['_TIME'] + 3600);

        return $result['ticket'];
    }

    public static function getJsSignPackage($url = null)
    {
        if (null === $url) {
            $url = getCurUrl();
        }

        // 去除#锚点后面的内容
        $url = explode('#', $url)[0];

        $timestamp   = time();
        $nonceStr    = Model_Weixin_Util::buildNonceStr();
        $jsApiTicket = self::getJsApiTicket();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $rawString = "jsapi_ticket=$jsApiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($rawString);

        return [
            'appId'     => WX_MP_APP_ID,
            'timestamp' => $timestamp,
            'nonceStr'  => $nonceStr,
            'signature' => $signature,
        ];
    }
}