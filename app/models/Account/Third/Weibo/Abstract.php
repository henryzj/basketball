<?php

/**
 * 第三方通行证
 * 新浪微博 OAuth2.0 抽象父类
 *
 * @author zhengjiang
 *
 * @link http://open.weibo.com/wiki/%E5%BE%AE%E5%8D%9AAPI
 */

Yaf_Loader::import(SYS_PATH . 'Third/Weibo/saetv2.ex.class.php');

abstract class Model_Account_Third_Weibo_Abstract extends Model_Account_Third_Abstract
{
    public function getPlatform()
    {
        return 'Weibo';
    }

    // 获取授权 AccessToken
    protected function _fetchAccessToken($code)
    {
        $oAuth = new SaeTOAuthV2($this->_appId, $this->_appSecret);

        $accessTokenInfo = $oAuth->getAccessToken('code', [
            'code'         => $code,
            'redirect_uri' => $this->getRedirectUri()
        ]);

       return $accessTokenInfo;
    }

    // 刷新、续约授权 AccessToken
    // Refresh Token 刷新的方式来延续授权有效期，
    // 但需要注意的是：只有使用微博官方移动SDK(3.0及以上版本）的移动应用，才可以从SDK的方法中获取到 Refresh Token。
    protected function _refreshAccessToken($refreshToken)
    {
        $oAuth = new SaeTOAuthV2($this->_appId, $this->_appSecret);

        $accessTokenInfo = $oAuth->getAccessToken('token', [
            'refresh_token' => $refreshToken,
        ]);

       return $accessTokenInfo;
    }

    public function getThirdUser()
    {
        // 特别注意
        // 若用户禁止授权，则重定向后不会带上code参数，仅会带上state参数
        if (! isset($this->_params['code']) || ! $this->_params['code']) {
            throws('你真的舍得离我而去吗？');
        }

        // 授权码
        $code = $this->_params['code'];

        // 1. 用 code 去获取 accessToken
        $accessTokenInfo = $this->_fetchAccessToken($code);

        // 2. 用 accessToken + thirdUid 去获取用户完整信息
        $client = new SaeTClientV2($this->_appId, $this->_appSecret, $accessTokenInfo['access_token']);
        $userInfo = $client->show_user_by_id($accessTokenInfo['uid']);

        return [
            'id'        => $accessTokenInfo['uid'],
            'name'      => $userInfo['name'],
            'at_info'   => $accessTokenInfo,
            'user_info' => $userInfo,
        ];
    }

    // 将第三方账号和指定官方账号绑定
    public function bindUidByThird($uid, array $thirdUser, $upAccountInfo = true)
    {
        parent::bindUidByThird($uid, $thirdUser, $upAccountInfo);

        // 存储用户昵称、头像等
        if ($upAccountInfo) {
            $this->_updateAccountInfo($uid, [
                'nickname'   => $thirdUser['user_info']['name'],
                'sex'        => $thirdUser['user_info']['gender'] == 'm' ? 1 : 2,
                'province'   => $thirdUser['user_info']['province'],
                'city'       => $thirdUser['user_info']['city'],
                'headimgurl' => $thirdUser['user_info']['avatar_large'] ?: $thirdUser['user_info']['profile_image_url'],
            ]);
        }
    }
}