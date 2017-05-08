<?php

/**
 * 七牛云存储
 * 核心方法集合
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_AliOss
{
    public static function getThumbUrl($orgImgUrl, $w = 750, $h = 750, $quality = 100)
    {
        $pathinfo = pathinfo($orgImgUrl);
        $ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';

        return $orgImgUrl . '@' . $w . 'w_' . $h . 'h_' . $quality . 'Q' . ($ext ? '.' . $ext : '');
    }
}