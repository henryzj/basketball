<?php

/**
 * 第三方通行证
 * PC网页使用微信账号登陆
 * http://www.morecruit.cn/account3rd/login/source/Weixin_Web
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Account_Third_Weixin_Web extends Model_Account_Third_Weixin_Abstract
{
    protected $_appId     = WX_WEB_APP_ID;
    protected $_appSecret = WX_WEB_APP_SECRET;

    public function getAuthorizeUrl($referUrl)
    {
        F('Session')->set('__referUrl', $referUrl);

        $params = [
            'appid'         => $this->_appId,
            'redirect_uri'  => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope'         => 'snsapi_login',
            'state'         => uniqid(),
        ];

        return 'https://open.weixin.qq.com/connect/qrconnect?' . http_build_query($params) . '#wechat_redirect';
    }
}