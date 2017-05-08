<?php

/**
 * 统一的图片尺寸输出
 *
 * @author JiangJian <silverd@sohu.com>
 */

class MyHelper_Thumb
{
    // Qiniu/AliOSS
    const STORAGE = 'Qiniu';

    public static function getSize($scene)
    {
        switch ($scene) {

            // 列表中的用户小头像
            case 'AvatarSmall':

                return ['w' => 56, 'h' => 56];

            // 个人主页用户大头像
            case 'AvatarBig':

                return ['w' => 120, 'h' => 120];

            // 体验摘要里的封面图
            case 'PhotoSmall':

                return ['w' => 100, 'h' => 100];

            // 动态中的图片
            // 全屏宽的正方形（高度和宽度一样）
            case 'FullWidthSquare':

                $_SCREEN_WIDTH  = isset($_SERVER['HTTP_C_SCREEN_WIDTH'])  ? $_SERVER['HTTP_C_SCREEN_WIDTH']  : 650;
                $_SCREEN_HEIGHT = $_SCREEN_WIDTH;

                return ['w' => $_SCREEN_WIDTH, 'h' => $_SCREEN_HEIGHT, 'no_scale' => 1];

            // 酒店头图、首页运营位背景图
            // 全屏宽的矩形（高度为宽的1/2）
            case 'FullWidthRectangle':

                $_SCREEN_WIDTH  = isset($_SERVER['HTTP_C_SCREEN_WIDTH'])  ? $_SERVER['HTTP_C_SCREEN_WIDTH']  : 650;
                $_SCREEN_HEIGHT = ceil($_SCREEN_WIDTH / 2);

                return ['w' => $_SCREEN_WIDTH, 'h' => $_SCREEN_HEIGHT, 'no_scale' => 1];

            // 全屏图
            case 'FullScreen':

                $_SCREEN_WIDTH  = isset($_SERVER['HTTP_C_SCREEN_WIDTH'])  ? $_SERVER['HTTP_C_SCREEN_WIDTH']  : 650;
                $_SCREEN_HEIGHT = isset($_SERVER['HTTP_C_SCREEN_HEIGHT']) ? $_SERVER['HTTP_C_SCREEN_HEIGHT'] : 1066;

                return ['w' => $_SCREEN_WIDTH, 'h' => $_SCREEN_HEIGHT, 'no_scale' => 1];
        }

        return false;
    }

    public static function getThumbUrl($imgUrl, $scene)
    {
        $_NETWORK      = isset($_SERVER['HTTP_C_NETWORK'])      ? $_SERVER['HTTP_C_NETWORK'] : 'UNKNOWN';
        $_SCREEN_SCALE = isset($_SERVER['HTTP_C_SCREEN_SCALE']) ? max(1, floatval($_SERVER['HTTP_C_SCREEN_SCALE'])) : 1;
        $_PIC_MODE     = isset($_SERVER['HTTP_C_PIC_MODE'])     ? intval($_SERVER['HTTP_C_PIC_MODE'])               : 1;

        // 无图模式
        if ($_PIC_MODE == 0) {
            return '';
        }

        // 始终大图模式
        elseif ($_PIC_MODE == 2) {
            $_NETWORK = 'WIFI';
        }

        // 否则自适应（WIFI加载大图，普通环境加载小图）
        // 根据网络情况决定图片质量
        switch ($_NETWORK) {

            case '3G':

                $quality = 80;
                break;

            case '2G':

                $quality = 50;
                break;

            case '4G':
            case 'WIFI':
            case 'UNKNOWN':
            default:

                $quality = 100;
                break;
        }

        // 根据场景决定图片尺寸
        if (! $size = self::getSize($scene)) {
            return $imgUrl;
        }

        $width  = intval($size['w']);
        $height = intval($size['h']);

        // 图片宽高缩放比
        if (! isset($size['no_scale']) || ! $size['no_scale']) {
            $width  = intval($width * $_SCREEN_SCALE);
            $height = intval($height * $_SCREEN_SCALE);
        }

        if (self::STORAGE == 'Qiniu') {
            return Com_Qiniu::getThumbUrl($imgUrl, $width, $height, $quality);
        }
        else {
            return Com_AliOss::getThumbUrl($imgUrl, $width, $height, $quality);
        }
    }

    /**
     * 处理指定一行的某些图片字段
     *
     * @param array/object $row
     * @param array $rules = [
     *     'AvatarSmall' => ['user_info'  => ['headimgurl']],
     *     'AvatarBig'   => ['hotel_info' => ['headimgurl']],
     * ]
     * @param bool $overwrite
     * @return array
     */
    public static function decoThumbUrl($row, array $rules, $overwrite = true)
    {
        if (! $row) {
            return $row;
        }

        foreach ($rules as $scene => $fields) {

            $_scene = strtolower($scene);

            foreach ((array) $fields as $key => $field) {
                if (is_array($field)) {
                    if (isset($row[$key]) && is_array($row[$key])) {
                        $row[$key] = self::decoThumbUrl($row[$key], [$scene => $field], $overwrite);
                    }
                }
                else {
                    if (isset($row[$field]) && $row[$field]) {
                        $result = self::getThumbUrl($row[$field], $scene);
                        if ($overwrite) {
                            // 覆盖原字段值
                            $row[$field] = $result;
                        } else {
                            // 不覆盖原字段值
                            $row['thumb_' . $_scene . '_' . $field] = $result;
                        }
                    }
                }
            }
        }

        return $row;
    }

    public static function decoThumbUrls(array $list, array $rules, $overwrite = true)
    {
        if ($list) {
            foreach ($list as &$row) {
                $row = self::decoThumbUrl($row, $rules, $overwrite);
            }
        }

        return $list;
    }

    public static function decoThumbList(array $imgUrls, $scene)
    {
        if ($imgUrls) {
            foreach ($imgUrls as &$imgUrl) {
                $imgUrl = MyHelper_Thumb::getThumbUrl($imgUrl, $scene);
            }
        }

        return $imgUrls;
    }
}