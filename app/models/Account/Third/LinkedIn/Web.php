<?php

/**
 * 第三方通行证
 * 领英快捷登录 网页版
 *
 * @author zhengjiang
 *
 * @link https://developer.linkedin.com/docs/signin-with-linkedin?u=0
 */

class Model_Account_Third_LinkedIn_Web extends Model_Account_Third_LinkedIn_Abstract
{
    public function getAuthorizeUrl($referUrl)
    {
        F('Session')->set('__referUrl', $referUrl);

        $params = [
            'client_id'     => $this->_appId,
            'redirect_uri'  => $this->getRedirectUri(),
            'response_type' => 'code',
            'state'         => uniqid(),
        ];

        return 'https://www.linkedin.com/uas/oauth2/authorization?' . http_build_query($params);
    }
}