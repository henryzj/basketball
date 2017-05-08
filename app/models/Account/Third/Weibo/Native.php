<?php

/**
 * 第三方通行证
 * 新浪微博 OAuth2.0 移动应用
 *
 * @author zhengjiang
 *
 * @link http://open.weibo.com/wiki/%E5%BE%AE%E5%8D%9AAPI
 */

class Model_Account_Third_Weibo_Native extends Model_Account_Third_Weibo_Abstract
{
    protected $_appId     = THIRD_PF_WEIBO_APP_ID;
    protected $_appSecret = THIRD_PF_WEIBO_APP_SECRET;

    // 移动应用 Android/iOS 客户端授权登陆后
    // 直接就返回了 access_token，而不是授权码 code
    public function getThirdUser()
    {
        // 空字段填充
        initDataFields($this->_params, ['access_token', 'expires_in', 'refresh_token']);

        $accessTokenInfo = [
            'access_token'  => $this->_params['access_token'],
            'expires_in'    => $this->_params['expires_in'],
            'refresh_token' => $this->_params['refresh_token'],
        ];

        if (! isset($this->_params['access_token']) || ! $this->_params['access_token']) {
            throws('你真的舍得离我而去吗？');
        }

        $client = new SaeTClientV2($this->_appId, $this->_appSecret, $accessTokenInfo['access_token']);

        // 获取授权用户的uid
        if (! $uidInfo = $client->get_uid()) {
            throws('用户信息不正确');
        }

        if (! isset($uidInfo['uid'])) {
            throws(isset($uidInfo['error']) ? $uidInfo['error'] : '微博uid获取失败');
        }

        // 获取用户的详细信息
        $userInfo = $client->show_user_by_id($uidInfo['uid']);

        return [
            'id'        => $userInfo['id'],
            'name'      => $userInfo['name'],
            'at_info'   => $accessTokenInfo,
            'user_info' => $userInfo,
        ];
    }
}