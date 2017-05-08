<?php

/**
 * 验证码生成、检测 (ThinkPHP)
 *
 * @author cnny
 */

class Com_CaptchaTP
{

    /**
     * 产生随机字串，可用来自动生成密码
     * 默认长度6位 字母和数字混合 支持中文
     *
     * @param string $len 长度
     * @param string $type 字串类型 0 字母 1 数字 其它 混合
     * @param string $addChars 额外字符
     * @return string
     */
    public static function randString($len = 6, $type = '', $addChars = '')
    {
        $str = '';
        switch ($type) {

            case 0:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 1:
                $chars = str_repeat('0123456789', 3);
                break;
            case 2:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
                break;
            case 3:
                $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            default :
                // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
                break;
        }

        // 位数过长重复字符串一定次数
        if ($len > 10) {
            $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
        }

        $chars = str_shuffle($chars);
        $str = substr($chars, 0, $len);

        return $str;
    }

    public static function build($length = 4, $mode = 1, $type = 'png', $width = 48, $height = 22, $verifyName = 'verify')
    {
        $randval = self::randString($length, $mode);

        // 存入SESSION中
        F('Session')->set($verifyName, md5($randval));

        $width = ($length * 10 + 10) > $width ? $length * 10 + 10 : $width;

        if ($type != 'gif' && function_exists('imagecreatetruecolor')) {

            $im = imagecreatetruecolor($width, $height);
        } else {

            $im = imagecreate($width, $height);
        }

        $r = array(225, 255, 255, 223);
        $g = array(225, 236, 237, 255);
        $b = array(225, 236, 166, 125);
        $key = mt_rand(0, 3);

        // 背景色（随机）
        $backColor = imagecolorallocate($im, $r[$key], $g[$key], $b[$key]);

        // 边框色
        $borderColor = imagecolorallocate($im, 100, 100, 100);

        imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $backColor);

        imagerectangle($im, 0, 0, $width - 1, $height - 1, $borderColor);
        $stringColor = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));

        // 干扰
        for ($i = 0; $i < 10; $i++) {
            imagearc($im, mt_rand(-10, $width), mt_rand(-10, $height), mt_rand(30, 300), mt_rand(20, 200), 55, 44, $stringColor);
        }

        for ($i = 0; $i < 25; $i++) {
            imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $stringColor);
        }

        for ($i = 0; $i < $length; $i++) {
            imagestring($im, 5, $i * 10 + 5, mt_rand(1, 8), $randval{$i}, $stringColor);
        }

        self::output($im, $type);

        exit();
    }


    /**
     * 检验验证码
     * @param  [type] $code [用户所填验证码]
     * @param  [type] $verifyname [用户所填验证码]
     */
    public static function check($code, $verifyName = 'verify')
    {
        $verifyCode = F('Session')->get($verifyName);

        return md5($code) == $verifyCode ? true : false;
    }

    /**
     * 输出图片
     * @param  [type] $im       [description]
     * @param  string $type     [description]
     * @param  string $filename [description]
     * @return [type]           [description]
     */
    public static function output($im, $type = 'png', $filename = '')
    {
        header("Content-type: image/" . $type);
        $imageFun = 'image' . $type;

        if (empty($filename)) {
            $imageFun($im);
        } else {
            $imageFun($im, $filename);
        }

        imagedestroy($im);
    }
}