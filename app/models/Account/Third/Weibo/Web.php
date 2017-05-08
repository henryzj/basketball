<?php

/**
 * 第三方通行证
 * 新浪微博 OAuth2.0 网页应用
 *
 * @author zhengjiang
 *
 * @link http://open.weibo.com/wiki/%E5%BE%AE%E5%8D%9AAPI
 */

class Model_Account_Third_Weibo_Web extends Model_Account_Third_Weibo_Abstract
{
    protected $_appId     = THIRD_PF_WEIBO_WEB_APP_ID;
    protected $_appSecret = THIRD_PF_WEIBO_WEB_APP_SECRET;

    public function getAuthorizeUrl($referUrl)
    {
        F('Session')->set('__referUrl', $referUrl);

        $oAuth = new SaeTOAuthV2($this->_appId, $this->_appSecret);

        return $oAuth->getAuthorizeURL($this->getRedirectUri());
    }
}