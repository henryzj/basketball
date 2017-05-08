<?php

/**
 * 第三方通行证
 * QQ互联 OAuth2
 *
 * @author JiangJian <silverd@sohu.com>
 */

abstract class Model_Account_Third_QQ_Abstract extends Model_Account_Third_Abstract
{
    public function getPlatform()
    {
        return 'QQ';
    }

    // 获取授权 AccessToken
    protected function _fetchAccessToken($code)
    {
        $params = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->_appId,
            'client_secret' => $this->_appSecret,
            'code'          => $code,
            'redirect_uri'  => $this->getRedirectUri(),
        ];

        $url = 'https://graph.qq.com/oauth2.0/token?' . http_build_query($params);

        $resp = file_get_contents($url);

        if (strpos($resp, 'callback') !== false) {
            $lpos = strpos($resp, '(');
            $rpos = strrpos($resp, ')');
            $resp = substr($resp, $lpos + 1, $rpos - $lpos -1);
            $msg  = json_decode($resp);
            if (isset($msg->error)) {
                throw new Exception($msg->error . '-' . $msg->error_description);
            }
        }

        // 解析成数组
        parse_str($resp, $accessTokenInfo);

        return $accessTokenInfo;
    }

    // 刷新、续约授权 AccessToken
    protected function _refreshAccessToken($refreshToken)
    {
        $params = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->_appId,
            'client_secret' => $this->_appSecret,
            'refresh_token' => $refreshToken,
        ];

        $url = 'https://graph.qq.com/oauth2.0/token?' . http_build_query($params);

        $resp = file_get_contents($url);

        if (strpos($resp, 'callback') !== false) {
            $lpos = strpos($resp, '(');
            $rpos = strrpos($resp, ')');
            $resp = substr($resp, $lpos + 1, $rpos - $lpos -1);
            $msg  = json_decode($resp);
            if (isset($msg->error)) {
                throw new Exception($msg->error . '-' . $msg->error_description);
            }
        }

        // 解析成数组
        parse_str($resp, $accessTokenInfo);

        return $accessTokenInfo;
    }

    protected function _getOpenId($accessToken)
    {
        $url = 'https://graph.qq.com/oauth2.0/me?access_token=' . $accessToken;

        $resp = file_get_contents($url);

        if (strpos($resp, 'callback') !== false) {
            $lpos = strpos($resp, '(');
            $rpos = strrpos($resp, ')');
            $resp = substr($resp, $lpos + 1, $rpos - $lpos -1);
        }

        $msg = json_decode($resp);

        if (isset($msg->error)) {
            throw new Exception($msg->error . '-' . $msg->error_description);
        }

        return $msg->openid;
    }

    // 根据授权 AccessToken 获取指定用户的个人信息
    public function getUserInfo($accessToken, $openId)
    {
        $params = [
            'access_token'       => $accessToken,
            'oauth_consumer_key' => $this->_appId,
            'openid'             => $openId,
            'format'             => 'json',
        ];

        $url = 'https://graph.qq.com/user/get_user_info?' . http_build_query($params);

        $userInfo = file_get_contents($url);
        $userInfo = json_decode($userInfo, true);

        if ($userInfo['ret'] != 0) {
            throw new Exception($userInfo['msg'], $userInfo['ret']);
        }

        // 过滤昵称中的 emoji 表情符号
        if (isset($userInfo['nickname'])) {
            $userInfo['nickname'] = Model_Weixin_Util::filterNickname($userInfo['nickname']);
        }

        return $userInfo;
    }

    // 获取已登录用户的关于QQ会员业务的详细资料。
    // 详细资料包括：用户会员的历史属性，用户会员特权的到期时间，用户最后一次充值会员业务的支付渠道，用户开通会员的主要驱动因素。
    // @link http://wiki.connect.qq.com/get_vip_rich_info
    public function getVipRichInfo($accessToken, $openId)
    {
        $params = [
            'access_token'       => $accessToken,
            'oauth_consumer_key' => $this->_appId,
            'openid'             => $openId,
            'format'             => 'json',
        ];

        $url = 'https://graph.qq.com/user/get_vip_rich_info?' . http_build_query($params);

        $userInfo = file_get_contents($url);
        $userInfo = json_decode($userInfo, true);

        if ($userInfo['ret'] != 0) {
            throw new Exception($userInfo['msg'], $userInfo['ret']);
        }

        return $userInfo;
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

        // 2. 用 accessToken 获取 openId
        $openId = $this->_getOpenId($accessTokenInfo['access_token']);

        // 3. 用 accessToken + openId 去获取用户完整信息
        $userInfo = $this->getUserInfo($accessTokenInfo['access_token'], $openId);

        return [
            'id'        => $openId,
            'name'      => $userInfo['nickname'],
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
                'nickname'   => $thirdUser['user_info']['nickname'],
                'sex'        => $thirdUser['user_info']['gender'] == '男' ? 1 : 2,
                'province'   => $thirdUser['user_info']['province'],
                'city'       => $thirdUser['user_info']['city'],
                'headimgurl' => $thirdUser['user_info']['figureurl_qq_2'] ?: $thirdUser['user_info']['figureurl_qq_1'],
            ]);
        }
    }
}