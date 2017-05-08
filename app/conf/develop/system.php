<?php

/**
 * 系统常量定义
 *
 * @author JiangJian <silverd@sohu.com>
 */

// 模板文件扩展名
define('TPL_EXT', '.phtml');

// 当前时区
define('CUR_TIMEZONE', 'Asia/Shanghai');

// 静态CDN地址（斜杠结尾）
define('CDN_PATH', '/');

// CSS存放目录（全局）
define('CSS_DIR', CDN_PATH . 'css');

// IMG存放目录（全局）
define('IMG_DIR', CDN_PATH . 'img');

// JS存放目录（全局）
define('JS_DIR',  CDN_PATH . 'js');

// 是否调试模式
define('DEBUG_MODE', true);

// 调试模式下，是否 Explain SQL
define('DEBUG_EXPLAIN_SQL', false);

// 数据库名前缀
define('DB_PREFIX', 'stay_');

// 新用户存储在哪几个库（最多两个，用半角逗号分隔）
define('DB_SUFFIX_NEW_USER', '1');

// 总共有几个用户分库
define('DIST_USER_DB_NUM', 1);

// 今天日期
define('TODAY', date('Y-m-d'));

// 静态文件是否需要按版本部署
define('STATIC_DEPLOY', false);

// 是否开启 XHProf 性能测试
define('XHPROF_DEGUG', true);