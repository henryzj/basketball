<?php

/**
 * 支付模型-函数集合
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Util.php 11759 2014-07-16 05:42:03Z jiangjian $
 */

class Model_Payment_Util extends Core_Model_Abstract
{
    public static function loadConfig($filePath, $__inSandBox = false)
    {
        return include APP_PATH . 'models/Payment/Util/' . $filePath . '.php';
    }

    public static function buildUrl($baseUrl, array $params = array())
    {
        return $baseUrl . (strpos($baseUrl, '?') === false ? '?' : '&') . ($params ? http_build_query($params) : '');
    }

    public static function buildUrlSignal($baseUrl, array $params = array())
    {
        return 'URL::' . self::buildUrl($baseUrl, $params);
    }

    public static function getUrlFromSignal($result)
    {
        if (! is_string($result)) {
            return '';
        }

        if (substr($result, 0, 5) == 'URL::') {
            return substr($result, 5);
        }
        else {
            return '';
        }
    }

    /**
     * 获取支付来路URL
     *
     * @return array $return
     * @return string $url
     */
    public static function dealRespReturn(array $return)
    {
        $referUrl = F('Session')->get('pay_refer_url');

        // 如果有回跳地址
        if ($referUrl) {
            // 则直接返回跳转地址
            return self::buildUrlSignal($referUrl, $return);
        }
        else {
            // 否则返回数据给控制器并渲染页面
            return $return;
        }
    }

    /**
     * 以表单HTML形式构造请求
     *
     * @param array $params 请求参数数组
     * @param string $method 提交方式 GET/POST
     * @param string $btnName 确认按钮显示文字
     * @return $html 提交表单HTML文本
     */
    public static function buildRequestForm($action, array $params, $method, $btnName = null)
    {
        $html = "<form id='payformsubmit' action='" . $action . "' method='" . $method . "'>";

        if ($params) {
            foreach ($params as $key => $value) {
                $html .= "<input type='hidden' name='" . $key . "' value='" . $value ."' />";
            }
        }

        $btnName = $btnName ?: '提交订单';

        $html .= "<input type='submit' value='" . $btnName . "' />";
        $html .= "</form>";
        $html .= "<script>document.getElementById('payformsubmit').submit();</script>";

        return $html;
    }
}