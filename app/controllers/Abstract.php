<?php

/**
 * 控制器抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Abstract.php 11636 2014-07-08 02:41:43Z jiangjian $
 */

abstract class Controller_Abstract extends Core_Controller_Web
{
    /**
     * 当前用户 uid
     *
     * @var int
     */
    protected $_uid;

    /**
     * 当前用户实例对象
     *
     * @var Model_User
     */
    protected $_user;

    /**
     * 是否检测登陆
     *
     * @var int
     *      0: 完全开放（游客可访问）
     *      1: 需要登陆
     */
    protected $_checkAuth = 1;

    /**
     * 构造函数
     */
    public function init()
    {
        parent::init();

        // 初始化全局数组
        $this->initGlobalVar();

        // 当前登陆用户uid
        $__vuser = $this->getx(Model_Account_Base::CLIENT_TICKET_NAME);
        $this->_uid = Model_Account_Base::getUidByTicket($__vuser);

        // 未登录
        if (! $this->_uid) {
            $this->checkAccess($this->_checkAuth);
        }

        // 已登录
        else {

            $this->_uniqUserKey = $this->_uid;

            // 初始化用户信息
            Model_Account_Base::initAppUser($this->_uid);

            // 创建用户实例
            $this->_user = new Model_User($this->_uid, true);

            // 更新用户的最后登录时间、IP等信息
            $this->_user->base->updateLastLogin();

            $this->assign([
                'uid'  => $this->_uid,
                'user' => $this->_user,
            ]);
        }
    }

    public function checkAccess($checkAuth = null)
    {
        if ($this->_uid) {
            return true;
        }

        if (null === $checkAuth) {
            $checkAuth = $this->_checkAuth;
        }

        // 必须登录才能访问
        if ($checkAuth) {
            $this->vRedirect('/auth/login/?refer=' . rawurlencode(getCurUrl()));
        }
    }

    /**
     * 初始化全局数组
     */
    public function initGlobalVar()
    {
        $GLOBALS['_CUR_URL'] = rawurlencode(getCurUrl());
    }

    public function vRedirect($url)
    {
        if ($this->isAjax()) {
            throws('AjaxJump::' . $url);
        }

        // 清除防刷间隔
        Com_AntiRefresh::clearReqInterval($this->_uid, $this->_request);

        $this->redirect($url);
    }

    public function vForward($controller, $action = null, $params = null)
    {
        // 清除防刷间隔
        Com_AntiRefresh::clearReqInterval($this->_uid, $this->_request);

        $this->forward($controller, $action, $params);
    }

    public function showJumpMsg(array $params)
    {
        if (! isset($params['icon'])) {
            $params['icon'] = 'mbox_icon/happy_400';
        }

        $this->assign('params', $params);
        $this->getView()->display('_msg/jump');
        exit();
    }

    public function showSuccessMsg(array $params)
    {
        $this->assign('params', $params);
        $this->getView()->display('_msg/success');
        exit();
    }

    public function showErrorMsg($title, $message)
    {
        $this->assign('title', $title ?: '404');
        $this->assign('message', $message ?: '网页找不到了~');
        $this->getView()->display('_msg/error');
        exit();
    }

    /**
     * 获取当前用户盐
     *
     * @param string $extraKey 额外密钥
     * @return string
     */
    public function getSalt($extraKey = null)
    {
        return __getSalt($extraKey);
    }

    // 防刷机制，每两次请求必须间隔X毫秒
    public function assertReqInterval($ms = 1000, $namespace = null)
    {
        $antiRefresh = new Com_AntiRefresh();

        if (! $antiRefresh->intervalReqLimit($this->_uid, $this->_request, $ms, $namespace)) {
            throws('您的动作太快了，请休息一会儿');
        }
    }

    // 防刷机制，N秒最多累计接受M次请求
    public function assertReqCumulative($N = 1, $M = 5, $namespace = null)
    {
        $antiRefresh = new Com_AntiRefresh();

        if (! $antiRefresh->cumulativeReqLimit($this->_uid, $this->_request, $N = 1, $M = 5, $namespace)) {
            throws('您的动作太快了，请休息一会儿');
        }
    }
}