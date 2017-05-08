<?php

/**
 * 第三方通行证
 * 领英 OAuth2.0 抽象父类
 *
 * @author zhengjiang
 *
 * @link https://developer.linkedin.com/docs/signin-with-linkedin?u=0
 */

Abstract class Model_Account_Third_LinkedIn_Abstract extends Model_Account_Third_Abstract
{
    protected $_appId     = THIRD_PF_LINKEDIN_APP_ID;
    protected $_appSecret = THIRD_PF_LINKEDIN_APP_SECRET;

    public function getPlatform()
    {
        return 'LinkedIn';
    }

    // 获取授权 AccessToken
    protected function _fetchAccessToken($code)
    {
        $params = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->_appId,
            'client_secret' => $this->_appSecret,
            'redirect_uri'  => $this->getRedirectUri(),
            'code'          => $code,
        ];

        $url = 'https://www.linkedin.com/uas/oauth2/accessToken';

        $result = Com_Http::request($url, $params, 'CURL-POST', true);
        $result = json_decode($result, true);

        if (isset($result['error_description'])) {
            throws($result['error_description']);
        }

        return $result;
    }

    // 获取授权 AccessToken
    protected function _getUserInfo($accessToken)
    {
        $url = 'https://api.linkedin.com/v1/people/~:(id,formatted-name,picture-url)?format=json';

        $headers = [
            'Host: api.linkedin.com',
            'Connection: Keep-Alive',
            'Authorization: Bearer ' . $accessToken,
        ];

        $result = Com_Http::advRequest('GET', $url, [], $headers);

        return json_decode($result['body'], true);
    }

    // 因为领英客户端的access_token和服务端的access_token不通用。
    // 所以客户端请采用 LinkedIn_Web (WebView) 形式来授权
    // 授权后客户端需拦截 URL 中的 code 并传递给 /account3rd/redirectOAuth/source/LinkedIn_Web
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

        if (! $accessTokenInfo || ! isset($accessTokenInfo['access_token']) || ! $accessTokenInfo['access_token']) {
            throws('accessToken不正确');
        }

        // 2. 用 accessToken 去获取用户完整信息
        $userInfo = $this->_getUserInfo($accessTokenInfo['access_token']);

        return [
            'id'        => $userInfo['id'],
            'name'      => $userInfo['formattedName'],
            'at_info'   => [
                'access_token' => $accessTokenInfo['access_token'],
                'expires_in'   => $accessTokenInfo['expires_in'],
            ],
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
                'nickname'   => $thirdUser['user_info']['formattedName'],
                'headimgurl' => $thirdUser['user_info']['pictureUrl'],
            ]);
        }
    }
}