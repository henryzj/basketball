<?php

/**
 * 微信-辅助函数集合
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_Util extends Core_Model_Abstract
{
    // 判断是不是微信浏览器
    public static function isWeixinBrowser()
    {
        if (! isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        return stripos($_SERVER['HTTP_USER_AGENT'], 'microMessenger') !== false ? true : false;
    }

    // 获取固定长度的随机字符串
    public static function buildNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charsLen = strlen($chars) - 1;
        $nonceStr ='';

        for ( $i = 0; $i < $length; $i++) {
            $nonceStr .= $chars[mt_rand(0, $charsLen)];
        }

        return $nonceStr;
    }

    /**
     * 拼接参数串（做签名前的准备）
     *
     * @param array $params
     * @param bool $urlencode
     * @return $string
     */
    public static function concatParams(array $params, $rawurlencode = false)
    {
        $return = '';

        ksort($params);

        foreach ($params as $k => $v) {
            if ($k != 'sign' && ! empty($v)) {
                if ($rawurlencode) {
                    $v = rawurlencode($v);
                }
                $return  .=  $k .'=' . $v .'&';
            }
        }

        return rtrim($return, '&');
    }

    /**
     * 过滤微信昵称中的emoji表情字符
     * 这种特殊字符合作用的Unicode 6标准来统一,采用4个bytes来存储一个emoji表情,
     * 如果不处理直接存储到MySQL5.5以下的版本会报错,当然想要MySQL存储这种字符也不困难,只需要修改数据库字符集为utf8mb4即可,
     * 但数据回传给网页或者移动客户端时则需要做兼容处理,所以我们暂时忽略这种需求,直接将其过滤掉.
     */
    public static function filterNickname($nickName)
    {
        return Helper_String::removeEmoji(trim($nickName)) ?: '表情帝';
    }
}