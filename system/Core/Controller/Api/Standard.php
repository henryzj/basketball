<?php

/**
 * Api 服务端控制器 标准版
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Api.php 7057 2013-11-25 08:41:34Z jiangjian $
 */

abstract class Core_Controller_Api_Standard extends Core_Controller_Api_Abstract
{
    protected $_appId;
    protected $_appVersion;

    public function init()
    {
        parent::init();

        $postData = $this->getPost();

        if (isset($_SERVER['HTTP_APPID']) && $_SERVER['HTTP_APPID']) {
            $this->_appId = $_SERVER['HTTP_APPID'];
        }
        elseif (isset($postData['app_id']) && $postData['app_id']) {
            $this->_appId = $postData['app_id'];
        }

        if (! $this->_appId) {
            $this->output('Invalid AppId', -9990);
        }

        if (isset($_SERVER['HTTP_APPVER']) && $_SERVER['HTTP_APPVER']) {
            $this->_appVersion = $_SERVER['HTTP_APPVER'];
        }
        elseif (isset($postData['app_version']) && $postData['app_version']) {
            $this->_appVersion = $postData['app_version'];
        }
        else {
            $this->_appVersion = '1.2.22';
        }

        // 当前APP信息
        $this->_appInfo = Dao('Core_V2AppInfo')->get($this->_appId);

        if (! $this->_appInfo) {
            $this->output('Invalid AppInfo', -9991);
        }

        if ($this->_appInfo['is_encrypted']) {
            if (! Helper_Api::verifySign($postData, $this->_appInfo['secret'])) {
                $this->output('签名验证失败', -9999);
            }
        }

        // 记录访问日志
        $this->_addAccessLog();
    }

    // 记录访问日志
    protected function _addAccessLog()
    {
        $module     = $this->_request->getModuleName();
        $controller = $this->_request->getControllerName();
        $action     = $this->_request->getActionName();
        $requestUri = strtolower('/' . $module . '/' . $controller . '/' . $action);

        if (! $requestInfo = Dao('Core_AccessLogConfig')->get($requestUri)) {
            return false;
        }

        if (! $requestInfo['status'] || $requestInfo['expired_at'] < $GLOBALS['_DATE']) {
            return false;
        }

        $accessData = [
            'header_data' => $_SERVER,
            'cookie_data' => $_COOKIE,
            'input_data'  => [
                'post' => $_POST,
                'get'  => $_GET,
                'raw'  => file_get_contents('php://input'),
            ],
        ];

        Dao('Massive_LogAccess')->insert([
            'name'       => $requestUri,
            'content'    => json_encode($accessData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => $GLOBALS['_DATE']
        ]);

        return true;
    }

    // 检测版本更新
    protected function _checkUpgrade($versionNo)
    {
        // 找出大于当前版本的所有版本
        if (! $versionList = Dao('Core_V2AppVersion')->getGreatVersionList($this->_appInfo['id'], $versionNo)) {
            return null;
        }

        // 如果其中有一个需要强制升级的版本（不一定是最新版本）
        // 那么也要弹出强制升级到最新版本的提示
        foreach ($versionList as $value) {
            if ($value['upgrade_type'] == 2) {
                return [
                    'version_no'   => $versionList[0]['version_no'],
                    'upgrade_type' => 2,
                    'upgrade_tips' => $versionList[0]['upgrade_tips'],
                    'apk_url'      => $versionList[0]['apk_url'],
                ];
            }
        }

        return [
            'version_no'   => $versionList[0]['version_no'],
            'upgrade_type' => 1,
            'upgrade_tips' => $versionList[0]['upgrade_tips'],
            'apk_url'      => $versionList[0]['apk_url'],
        ];
    }
}