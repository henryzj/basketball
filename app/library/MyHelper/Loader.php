<?php

// 版本号文件存放目录
define('VERSION_FILE_DIR', DATA_PATH . 'versions/');

class MyHelper_Loader
{
    /**
     * 需要压缩合并的JS文件
     *
     * @return array
     */
    public static function getJsList()
    {
        return include VERSION_FILE_DIR . 'js-list.php';
    }

    /**
     * 需要压缩合并的CSS文件
     *
     * @return array
     */
    public static function getCssList()
    {
        return include VERSION_FILE_DIR . 'css-list.php';
    }

    public static function importCss($scripts)
    {
        if (! $scripts) {
            return false;
        }

        $DIR = CSS_DIR;

        $cssList = self::getCssList();

        // 读取版本文件
        $versions = self::getVersions('css');

        if (! is_array($scripts)) {
            $scripts = array($scripts);
        }

        $html = '';

        foreach ($scripts as $script) {
            if (! STATIC_DEPLOY) {
                // 例如 all.css
                if (is_array($cssList[$script])) {
                    $html .= self::importCss($cssList[$script]);
                }
                else {
                    $html .= '<link rel="stylesheet" type="text/css" href="' . $DIR . '/' . $script . '.css" />';
                }
            }
            else {
                // 该文件已纳入版控
                if (isset($versions[$script])) {
                    $html .= '<link rel="stylesheet" type="text/css" href="'. $DIR . '_product/' . $script . '.' . $versions[$script] . '.css" />';
                }
                // 未纳入版控，则访问原始文件
                else {
                    $html .= '<link rel="stylesheet" type="text/css" href="' . $DIR . '/' . $script . '.css" />';
                }
            }
        }

        return $html;
    }

    public static function importJs($scripts)
    {
        if (! $scripts) {
            return false;
        }

        $DIR = JS_DIR;

        $jsList = self::getJsList();

        // 读取版本文件
        $versions = self::getVersions('js');

        if (! is_array($scripts)) {
            $scripts = array($scripts);
        }

        $html = '';

        foreach ($scripts as $script) {
            if (! STATIC_DEPLOY) {
                // 例如 all.js
                if (is_array($jsList[$script])) {
                    $html .= self::importJs($jsList[$script]);
                }
                else {
                    $html .= '<script type="text/javascript" src="' . $DIR  . '/' . $script . '.js"></script>';
                }
            }
            else {
                // 该文件已纳入版控
                if (isset($versions[$script])) {
                    $html .= '<script type="text/javascript" src="' . $DIR  . '_product/' . $script . '.' . $versions[$script] . '.js"></script>';
                }
                // 未纳入版控，则访问原始文件
                else {
                    $html .= '<script type="text/javascript" src="' . $DIR  . '/' . $script . '.js"></script>';
                }
            }
        }

        return $html;
    }

    // 单例、避免重复引入
    private static $_versions = [];

    public static function getVersions($TYPE)
    {
        if (! isset(self::$_versions[$TYPE])) {
            $fileName = VERSION_FILE_DIR . $TYPE . '.php';
            if (is_file($fileName)) {
                self::$_versions[$TYPE] = include $fileName;
            }
            else {
                self::$_versions[$TYPE] = [];
            }
        }

        return self::$_versions[$TYPE];
    }

    public static function setVersions($TYPE, array $versions = [])
    {
        if (! is_dir(VERSION_FILE_DIR)) {
            mkdir(VERSION_FILE_DIR, 0777, true);
        }

        $fileName = VERSION_FILE_DIR . $TYPE . '.php';
        $content  = '<?php return ' . var_export($versions, true) . ';';

        file_put_contents($fileName, $content);
    }

    public static function clsVersions($TYPE)
    {
        return self::setVersions($TYPE, []);
    }
}