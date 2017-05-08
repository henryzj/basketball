<?php

// 框架初始化
Core_Bootstrap::init();

class Bootstrap extends Yaf_Bootstrap_Abstract
{
    public function _initView(Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->setView(Core_View::getInstance());
    }

    public function _initConfig(Yaf_Dispatcher $dispatcher)
    {
        require_once RESOURCE_PATH . 'global-vars.php';
    }

    public function _initPlugin(Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->registerPlugin(new Plugin_RouterUri());
    }
}