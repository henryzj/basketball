<?php

/**
 * Web 访问入口
 *
 * @author JiangJian <silverd@sohu.com>
 */

// 定义路径常量
define('APP_PATH', dirname(__DIR__) . '/');
define('SYS_PATH', dirname(APP_PATH) . '/system/');

// 调试模式密钥
define('DEBUG_XKEY', 'HiCrew@morecruit');

// 配置文件
Yaf_Loader::import(APP_PATH . 'conf/conf.php');

$app = new Yaf_Application(CONF_PATH . 'app.ini');
$app->bootstrap()->run();