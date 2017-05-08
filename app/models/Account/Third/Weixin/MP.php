<?php

/**
 * 第三方通行证
 * 微信公众号 OAuth2 登陆
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Account_Third_Weixin_MP extends Model_Account_Third_Weixin_Abstract
{
    protected $_appId     = WX_MP_APP_ID;
    protected $_appSecret = WX_MP_APP_SECRET;

    public function getAuthorizeUrl($referUrl)
    {
        F('Session')->set('__referUrl', $referUrl);

        $params = [
            'appid'         => $this->_appId,
            'redirect_uri'  => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope'         => 'snsapi_userinfo',
            'state'         => uniqid(),
        ];

        return 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query($params) . '#wechat_redirect';
    }
}