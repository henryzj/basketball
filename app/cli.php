<?php

/**
 * 命令行入口
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: cli.php 5929 2013-09-23 15:23:11Z jiangjian $
 */

// 定义路径常量
define('APP_PATH', __DIR__ . '/');
define('SYS_PATH', dirname(APP_PATH) . '/system/');

// 配置文件
Yaf_Loader::import(APP_PATH . 'conf/conf.php');

$app = new Yaf_Application(CONF_PATH . 'app.ini');
$response = $app->bootstrap()
                ->getDispatcher()
                ->dispatch(new Yaf_Request_Simple());