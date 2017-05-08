<?php

/**
 * 微信-综合相关
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_Common extends Core_Model_Abstract
{
    // @link http://mp.weixin.qq.com/wiki/13/43de8269be54a0a6f64413e4dfa94f39.html
    public static function syncMenu(array $menus)
    {
        /*
        $menus = [
            "button" => [
                [
                    "name" => "方倍工作室",
                    "sub_button" => [
                        [
                            "type" => "click",
                            "name" => "公司简介",
                            "key" => "公司简介"
                        ],
                        [
                            "type" => "click",
                            "name" => "社会责任",
                            "key" => "社会责任"
                        ],
                        [
                            "type" => "click",
                            "name" => "联系我们",
                            "key" => "联系我们"
                        ],
                    ],
                ],
            ]
        ];
        */

        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create';
        $result = Model_Weixin_Core::curlWithAccessToken($url, json_encode($menus, JSON_UNESCAPED_UNICODE));

        return $result;
    }
}