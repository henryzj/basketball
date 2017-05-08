<?php

/**
 * 文件、目录处理函数集
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Helper_File
{
    // 遍历文件夹下所有文件
    // 包括子目录下的文件
    public static function getFileTree($path)
    {
        $tree = [];

        foreach (glob($path . '/*') as $single) {
            if (is_dir($single)) {
                $tree = array_merge($tree, self::getFileTree($single));
            }
            else {
                $tree[] = $single;
            }
        }

        return $tree;
    }
}