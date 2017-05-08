<?php

/**
 * 第三方通行证
 * 微信 OAuth2 抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 */

abstract class Model_Account_Third_Weixin_Abstract extends Model_Account_Third_Abstract
{
    public function getPlatform()
    {
        return 'Weixin';
    }

    // 获取授权 AccessToken
    // 注意这里和官方公众号全局 AccessToken 是不同的
    protected function _fetchAccessToken($code)
    {
        $params = [
            'grant_type' => 'authorization_code',
            'appid'      => $this->_appId,
            'secret'     => $this->_appSecret,
            'code'       => $code,
        ];

        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . http_build_query($params);

        return Model_Weixin_Core::curl($url);
    }

    // 刷新、续约授权 AccessToken
    protected function _refreshAccessToken($refreshToken)
    {
        $params = [
            'grant_type'    => 'refresh_token',
            'appid'         => $this->_appId,
            'refresh_token' => $refreshToken,
        ];

        $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?' . http_build_query($params);

        return Model_Weixin_Core::curl($url);
    }

    // 根据授权 AccessToken 获取指定用户的个人信息
    public function getUserInfo($accessToken, $openId)
    {
        $params = [
            'access_token' => $accessToken,
            'openid'       => $openId,
            'lang'         => 'zh_CN',
        ];

        $url = 'https://api.weixin.qq.com/sns/userinfo?' . http_build_query($params);

        if (! $userInfo = Model_Weixin_Core::curl($url)) {
            return $userInfo;
        }

        // 过滤昵称中的 emoji 表情符号
        if (isset($userInfo['nickname'])) {
            $userInfo['nickname'] = Model_Weixin_Util::filterNickname($userInfo['nickname']);
        }

        if (! isset($userInfo['unionid'])) {
            $userInfo['unionid'] = $userInfo['openid'];
        }

        return $userInfo;
    }

    public function getUserInfoByCode($code)
    {
        if (! $code) {
            throws('OAuth2 授权码不能为空');
        }

        $result = $this->_fetchAccessToken($code);

        return $this->getUserInfo($result['access_token'], $result['openid']);
    }

    // 查找该第三方账号对应的我方账号
    public function getUidByThird(array $thirdUser)
    {
        $uid = parent::getUidByThird($thirdUser);

        // 当用 Weixin_Web 先登录，然后再用 Weixin_MP 登录，需要补填公众号openid信息
        if ($uid > 0) {
            // 微信用户openid和unionid的关联
            $this->_updateWxUnionInfo($thirdUser['user_info']['unionid'], $thirdUser['user_info']['openid']);
        }

        return $uid;
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

        // 2. 用 accessToken 去获取用户完整信息
        $userInfo = $this->getUserInfo($accessTokenInfo['access_token'], $accessTokenInfo['openid']);

        return [
            'id'        => $userInfo['unionid'],
            'openid'    => $userInfo['openid'],
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
            $this->_updateAccountInfo($uid, $thirdUser['user_info']);
        }

        // 微信用户openid和unionid的关联
        $this->_updateWxUnionInfo($thirdUser['user_info']['unionid'], $thirdUser['user_info']['openid']);
    }

    // 微信用户openid和unionid的关联
    protected function _updateWxUnionInfo($unionId, $openId)
    {
        // 已关联过
        if (Dao('Ucenter_WxUnion')->get([$unionId, $this->_source, $this->_appId])) {
            return true;
        }

        return Dao('Ucenter_WxUnion')->insert([
            'unionid'    => $unionId,
            'source'     => $this->_source,
            'app_id'     => $this->_appId,
            'openid'     => $openId,
            'created_at' => $GLOBALS['_DATE'],
        ]);
    }
}