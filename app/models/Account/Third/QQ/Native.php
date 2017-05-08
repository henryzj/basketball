<?php

/**
 * 第三方通行证
 * QQ互联 OAuth2
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Account_Third_QQ_Native extends Model_Account_Third_QQ_Abstract
{
    protected $_appId     = THIRD_PF_QQ_APP_ID;
    protected $_appSecret = THIRD_PF_QQ_APP_SECRET;

    // 移动应用 Android/iOS 客户端授权登陆后
    // 直接就返回了 access_token，而不是授权码 code
    public function getThirdUser()
    {
        // 空字段填充
        initDataFields($this->_params, ['access_token', 'openid', 'expires_at']);

        $accessTokenInfo = [
            'access_token' => $this->_params['access_token'],
            'expires_in'   => $this->_params['expires_at'] - $GLOBALS['_TIME'],
        ];

        if (! isset($this->_params['access_token']) || ! $this->_params['access_token']) {
            throws('你真的舍得离我而去吗？');
        }

        // 3. 用 accessToken + openId 去获取用户完整信息
        $userInfo = $this->getUserInfo($accessTokenInfo['access_token'], $this->_params['openid']);

        return [
            'id'        => $this->_params['openid'],
            'name'      => $userInfo['nickname'],
            'at_info'   => $accessTokenInfo,
            'user_info' => $userInfo,
        ];
    }
}