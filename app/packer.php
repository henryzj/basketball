<?php

/**
 * JS/CSS 打包器（压缩、变更后自动重命名）
 *
 * @author JiangJian <silverd@sohu.com>
 */

// 以下常量需要自定义
$WEB_PATH = __DIR__ . '/web/';

define('DATA_PATH', __DIR__ . '/data/');
include __DIR__ . '/library/MyHelper/Loader.php';

(new Packer($WEB_PATH))->execute();

// ============ 以下请勿修改 ============
class Packer
{
    // 是否压缩JS
    const UGLIFY_JS = 1;

    public $WEB_PATH;

    public function __construct($WEB_PATH)
    {
        $this->WEB_PATH = $WEB_PATH;
    }

    // 命令行执行： /usr/bin/php packer.php
    public function execute()
    {
        // 完全重新生成版控文件
        if (isset($GLOBALS['argv'][1]) && $GLOBALS['argv'][1] == 'reset') {
            MyHelper_Loader::clsVersions('js');
            MyHelper_Loader::clsVersions('css');
        }

        $this->__process('js', MyHelper_Loader::getJsList());
        $this->__process('css', MyHelper_Loader::getCssList());

        exit('JS/CSS packed successfully.' . PHP_EOL);
    }

    private function __process($TYPE, array $scripts)
    {
        // 读取版本文件
        $versions = MyHelper_Loader::getVersions($TYPE);

        // 文件类型后缀
        $EXT = $TYPE;

        foreach ($scripts as $key => $script) {
            if (is_array($script)) {
                $changed = 0;
                foreach ($script as $_script) {
                    if ($this->__compareAndCopy($TYPE, $_script, $EXT, $versions)) {
                        $changed++;
                    }
                }
                // 如果子文件有修改，则重新合并文件
                if ($changed > 0) {
                    $this->__merge($TYPE, $key, $script, $EXT, $versions);
                }
            }
            else {
                $this->__compareAndCopy($TYPE, $script, $EXT, $versions);
            }
        }

        // 更新版本文件
        MyHelper_Loader::setVersions($TYPE, $versions);
    }

    private function __compareAndCopy($DIR, $script, $EXT, &$versions)
    {
        $orgFile = $this->WEB_PATH . $DIR .'/' . $script . '.' . $EXT;
        $newMd5  = md5_file($orgFile);

        // 文件没有发生变更
        if (isset($versions[$script]) && $versions[$script] == $newMd5) {
            return false;
        }

        // 删除旧文件
        if (isset($versions[$script]) && $versions[$script]) {
            unlink($this->WEB_PATH . $DIR . '_product/' . $script . '.' . $versions[$script] . '.' . $EXT);
        }

        // 拷贝新文件
        $newFile = $this->WEB_PATH . $DIR . '_product/' . $script . '.' . $newMd5 . '.' . $EXT;

        $newFileDir = dirname($newFile);
        if (! is_dir($newFileDir)) {
            mkdir($newFileDir, 0777, true);
        }

        copy($orgFile, $newFile);

        // 压缩JS（第三方库除外）
        if (self::UGLIFY_JS) {
            if ($EXT == 'js' && strpos($script, 'vendor') === false) {
                $uglyFile = $this->WEB_PATH . $DIR . '_ugly/' . $script . '.' . $newMd5 . '.' . $EXT;
                $uglyFileDir = dirname($uglyFile);
                if (! is_dir($uglyFileDir)) {
                    mkdir($uglyFileDir, 0777, true);
                }
                $this->__compressJs($newFile, $uglyFile);
            }
        }

        return $versions[$script] = $newMd5;
    }

    private function __merge($DIR, $mergeName, array $scripts, $EXT, &$versions)
    {
        $all = '';

        foreach ($scripts as $script) {
            $sourceFile = $this->WEB_PATH . $DIR . '_product/' . $script . '.' . $versions[$script] . '.' . $EXT;
            $all .= file_get_contents($sourceFile);
        }

        // 删除旧的合并后文件
        if (isset($versions[$mergeName]) && $versions[$mergeName]) {
            unlink($this->WEB_PATH . $DIR . '_product/'. $mergeName . '.' . $versions[$mergeName] . '.' . $EXT);
        }

        // 写入新文件 all.js
        $mergeFile = $this->WEB_PATH . $DIR . '_product/'. $mergeName . '.' . $EXT;
        file_put_contents($mergeFile, $all);

        // 读取 all.js 自身版本号
        $mergeFileMd5 = md5_file($mergeFile);

        // 将 all.js 重命名（文件名中带上自身版本号）
        $mergeNewFile = $this->WEB_PATH . $DIR . '_product/'. $mergeName . '.' . $mergeFileMd5 . '.' . $EXT;
        rename($mergeFile, $mergeNewFile);

        // 压缩JS（第三方库除外）
        if (self::UGLIFY_JS) {
            if ($EXT == 'js' && strpos($mergeName, 'vendor') === false) {
                $uglyFile = $this->WEB_PATH . $DIR . '_ugly/'. $mergeName . '.' . $mergeFileMd5 . '.' . $EXT;
                $this->__compressJs($mergeNewFile, $uglyFile);
            }
        }

        return $versions[$mergeName] = $mergeFileMd5;
    }

    // 压缩JS
    private function __compressJs($sourceFile, $uglyFile)
    {
        $uglyDir = dirname($uglyFile);
        if (! is_dir($uglyDir)) {
            mkdir($uglyDir, 0777, true);
        }

        $cmd = 'uglifyjs ' . $sourceFile . ' -c -m -o ' . $uglyFile;
        exec($cmd);

        // 从临时目录移动到正式目录
        unlink($sourceFile);
        copy($uglyFile, $sourceFile);
        unlink($uglyFile);
    }
}