<?php

/**
 * 第三方通行证
 * QQ互联 OAuth2
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Account_Third_QQ_Web extends Model_Account_Third_QQ_Abstract
{
    protected $_appId     = THIRD_PF_QQ_WEB_APP_ID;
    protected $_appSecret = THIRD_PF_QQ_WEB_APP_SECRET;

    public function getAuthorizeUrl($referUrl)
    {
        F('Session')->set('__referUrl', $referUrl);

        $params = [
            'client_id'     => $this->_appId,
            'redirect_uri'  => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope'         => 'get_user_info',
            'state'         => uniqid(),
        ];

        return 'https://graph.qq.com/oauth2.0/authorize?' . http_build_query($params);
    }
}