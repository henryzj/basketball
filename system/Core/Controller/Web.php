<?php

/**
 * Web 控制器抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Web.php 7047 2013-11-25 02:51:14Z jiangjian $
 */

abstract class Core_Controller_Web extends Core_Controller_Abstract
{
    /**
     * 自动加载视图
     *
     * @var bool
     */
    public $yafAutoRender = true;

    /**
     * 传出模板变量
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function assign($key, $value = null)
    {
        return $this->_view->assign($key, $value);
    }

    /**
     * 设置模板布局
     *
     * @param string $layout
     */
    public function setLayout($layout = null)
    {
        $this->_view->setLayout($layout);
    }

    /**
     * 页面小组件
     *
     * @param string $tpl
     * @param array $data
     * @return string
     */
    public function widget($tpl, array $data = [], $return = false)
    {
        if ($return) {
            return $this->getView()->render($tpl, $data);
        }

        $this->getView()->display($tpl, $data);
    }

    /**
     * 是否自动渲染视图文件
     *
     * @param bool $bool
     */
    public function autoRender($bool = true)
    {
        $this->yafAutoRender = (bool) $bool;
    }

    public function alert($msg, $resultType = 'success', $url = '', $extra = '')
    {
        if (is_array($msg)) {
            $msg = implode('\n', $msg);
        }

        // Ajax
        if ($this->isAjax()) {
            $this->jsonx($msg, $resultType);
        }

        // 跳转链接
        if ($url == 'halt') {
            $jumpStr = '';
        } else {
            $url = $url ? $url : $this->refer();
            $url = $url ? $url : '/';
            $jumpStr = $url ? "top.location.href = '{$url}';" : '';
        }

        $this->js("top.alert('{$msg}'); {$extra} {$jumpStr}");
    }

    public function js($script, $exit = true)
    {
        echo('<script type="text/javascript">' . $script . '</script>');
        $exit && exit();
    }

    public function jump($url = '')
    {
        $url = $url ?: $this->refer();
        $this->js('top.location.href = \'' . $url . '\';');
    }

    public function refer()
    {
        return $this->getz('refer') ?: (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
    }

    // 生成新的 formHash
    public function refreshFormHash($formName)
    {
        // 生成随机码
        $formHash = $this->getSalt(uniqid(mt_rand(1, 10000)));

        // 将 formHash 写入到 Memcache 中
        $cacheKey = md5('formHash:' . $formName . ':' . $this->_uniqUserKey);
        F('Memcache')->set($cacheKey, $formHash);

        // 传出到模板中
        $this->assign('formHash', $formHash);

        return $formHash;
    }

    /**
     * 验证 formHash 是否正确
     *
     * @param string $formName
     * @param mixed $formHashGet
     * @param bool $clear 是否取完即清除
     * @return bool
     */
    public function verifyFormHash($formName, $formHashGet = null, $clear = true)
    {
        if ($formHashGet === null) {
            $formHashGet = $this->getx('hash');
        }

        if (! $formHashGet) {
            return false;
        }

        // 从 Memcache 中读取 formHash
        $cacheKey = md5('formHash:' . $formName . ':' . $this->_uniqUserKey);

        // 取完即清除（formHash只能用一次）
        $formHash = F('Memcache')->get($cacheKey);
        $clear && F('Memcache')->delete($cacheKey);

        return $formHash == $formHashGet ? true : false;
    }

    public function redirect($url)
    {
        parent::redirect($url);
        exit();
    }
}