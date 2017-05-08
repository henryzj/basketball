<?php

/**
 * 第三方通行证 抽象模型父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Abstract.php 10970 2014-05-15 05:38:03Z jiangjian $
 */

abstract class Model_Account_Third_Abstract extends Core_Model_Abstract implements Model_Account_Third_Interface
{
    /**
     * 平台渠道
     *
     * @var string
     */
    protected $_source;

    protected $_params = [];

    protected $_redirectUri;

    public function __construct($source)
    {
        $this->_source = $source;
    }

    public function getPlatform()
    {
        return $this->_source;
    }

    public function setParams(array $params)
    {
        $this->_params = $params;

        return $this;
    }

    public function getAuthorizeUrl($referUrl)
    {
        return '';
    }

    public function getRedirectUri()
    {
        return $this->_redirectUri ?: ('http://' . $GLOBALS['SITE_HOST'] . '/account3rd/redirectOAuth/source/' . $this->_source);
    }

    public function setRedirectUri($uri)
    {
        $this->_redirectUri = $uri;
    }

    // 将指定第三方账号和指定官方账号绑定
    public function bindUidByThird($uid, array $thirdUser, $upAccountInfo = true)
    {
        if (! isset($thirdUser['id']) || ! $thirdUser['id']) {
            throws('第三方用户 Uid 不合法');
        }

        $thirdUid = $thirdUser['id'];
        $platform = $this->getPlatform();

        if (Model_Account_Base::hasBindedThird($uid, $platform)) {
            throws('该官方账号已绑定了该渠道的其他第三方账号');
        }

        if (Dao('Ucenter_AccountThirdPfBind')->get([$platform, $thirdUid])) {
            throws('该渠道的第三方账号已绑定了其他官方账号');
        }

        // 建立绑定关系
        $setArr = [
            'platform'  => $platform,
            'third_uid' => $thirdUid,
            'uid'       => $uid,
        ];

        if (! Dao('Ucenter_AccountThirdPfBind')->insert($setArr)) {
            return false;
        }

        // 保存 accessToken
        if (isset($thirdUser['at_info']) && $thirdUser['at_info']) {
            $this->_updateAccessToken($uid, $thirdUser['at_info']);
        }

        return true;
    }

    // 查找该第三方账号对应的我方账号
    public function getUidByThird(array $thirdUser)
    {
        if (! isset($thirdUser['id']) || ! $thirdUser['id']) {
            throws('第三方用户 Uid 不合法');
        }

        $thirdUid    = $thirdUser['id'];
        $platformSrc = $this->getPlatform();

        $uid = Dao('Ucenter_AccountThirdPfBind')->getField([$platformSrc, $thirdUid], 'uid');

        return $uid;
    }

    // 查找该第三方账号对应的我方账号（找不到则帮他注册一下）
    public function fetchUidByThird(array $thirdUser)
    {
        // 查找该第三方账号对应的我方账号
        $uid = $this->getUidByThird($thirdUser);

        // 找不到则帮他注册一下
        if ($uid < 1) {

            $platform = $this->getPlatform();

            // 创建“用户中心”的一个新用户
            $uid = Model_Account_Base::createUcenterUser([
                'mobile'   => $platform . '_' . $thirdUser['id'] . '@3rd',
                'password' => '',
                'platform' => $platform,    // 空表示官方渠道
            ]);

            if ($uid < 1) {
                throws('网络繁忙，请稍后重试');
            }

            // 将第三方账号和当前官方账号绑定
            $this->bindUidByThird($uid, $thirdUser, true);
        }

        return $uid;
    }

    protected function _updateAccountInfo($uid, array $data)
    {
        if (! $data) {
            return false;
        }

        // 将第三方头像地址转存到七牛（防止第三方平台图片过期）
        if (isset($data['headimgurl']) && $data['headimgurl']) {
            try {
                $imgKey = Com_Qiniu::fetchSaveRemote($data['headimgurl']);
                $data['headimgurl'] = QINIU_DEF_DNHOST . '/'. $imgKey;
            }
            catch (Exception $e) {
                // do nothing
            }
        }

        // 昵称不能重复（自动加后缀）
        do {
            if ($findUid = Dao('Core_UserIndex')->getUidByName($data['nickname'])) {
                $data['nickname'] .= '_' . mt_rand(1, 9999);
            }
        }
        while ($findUid > 0);

        // 存储用户昵称、头像等
        $setArr = [
            'nickname'   => $data['nickname'],
            'sex'        => isset($data['sex'])        ? $data['sex']        : 0,
            'province'   => isset($data['province'])   ? $data['province']   : '',
            'city'       => isset($data['city'])       ? $data['city']       : '',
            'country'    => isset($data['country'])    ? $data['country']    : '',
            'language'   => isset($data['language'])   ? $data['language']   : 'zh_CN',
            'headimgurl' => isset($data['headimgurl']) ? $data['headimgurl'] : '',
        ];

        if (Dao('Ucenter_AccountInfo')->get($uid)) {
            Dao('Ucenter_AccountInfo')->updateByPk($setArr, $uid);
        }
        else {
            $setArr['uid'] = $uid;
            Dao('Ucenter_AccountInfo')->insert($setArr);
        }

        return Dao('Core_UserIndex')->updateByPk($setArr, $uid);
    }

    protected function _updateAccessToken($uid, array $tokenInfo)
    {
        if (! $tokenInfo) {
            return false;
        }

        $setArr = [
            'uid'                     => $uid,
            'source'                  => $this->_source,
            'app_id'                  => $this->_appId,
            'access_token'            => $tokenInfo['access_token'],
            'access_token_expires_at' => $tokenInfo['expires_in'] + $GLOBALS['_TIME'],
            'refresh_token'           => isset($tokenInfo['refresh_token']) ? $tokenInfo['refresh_token'] : '',
        ];

        return Dao('Ucenter_AccountThirdPfAccessToken')->replaceByPk($setArr, [$uid, $this->_source, $this->_appId]);
    }

    protected function _refreshAccessToken($refreshToken)
    {
        return false;
    }

    // 验证、续约授权 AccessToken
    public function getAccessToken($uid)
    {
        // 获取 accessToken 详情
        $tokenInfo = Dao('Ucenter_AccountThirdPfAccessToken')->get([$uid, $this->_source, $this->_appId]);

        // 如果 accessToken 不存在
        if (! $tokenInfo) {
            return null;
        }

        // 如果 accessToken 已经过期
        if ($tokenInfo['access_token_expires_at'] < $GLOBALS['_TIME']) {

            // 则根据 refreshToken 重新获取 accessToken
            if (! $tokenInfo = $this->_refreshAccessToken($tokenInfo['refresh_token'])) {
                throws('OAuth2 RefreshToken 已失效');
            }

            // 覆盖保存 accessToken
            $setArr = [
                'access_token'            => $tokenInfo['access_token'],
                'access_token_expires_at' => $tokenInfo['expires_in'] + $GLOBALS['_TIME'],
                'refresh_token'           => $tokenInfo['refresh_token'],
            ];

            Dao('Ucenter_AccountThirdPfAccessToken')->updateByPk($setArr, [$uid, $this->_source, $this->_appId]);
        }

        return $tokenInfo['access_token'];
    }
}