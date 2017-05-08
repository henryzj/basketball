<?php

/**
 * 第三方通行证
 * 支付宝快捷登录
 *
 * @author JiangJian <silverd@sohu.com>
 */

Yaf_Loader::import(SYS_PATH . 'Third/Alipay/alipay_submit.class.php');
Yaf_Loader::import(SYS_PATH . 'Third/Alipay/alipay_notify.class.php');

class Model_Account_Third_Alipay extends Model_Account_Third_Abstract
{
    public function getAuthorizeUrl($referUrl)
    {
        F('Session')->set('__referUrl', $referUrl);

        // 加载支付宝配置文件
        $config = include CONF_PATH . 'alipay/config.php';

        $params = [
            'service'           => 'alipay.auth.authorize',
            'partner'           => $config['partner'],
            'target_service'    => 'user.auth.quick.login',
            'return_url'        => $this->getRedirectUri(),
            'anti_phishing_key' => '',
            'exter_invoke_ip'   => '',
            '_input_charset'    => trim(strtolower($config['input_charset'])),
        ];

        $alipaySubmit = new AlipaySubmit($config);

        return $alipaySubmit->buildRequestUrl($params);
    }

    public function getThirdUser()
    {
        // 加载支付宝配置文件
        $config = include CONF_PATH . 'alipay/config.php';

        $alipayNotify = new AlipayNotify($config);

        if (! $alipayNotify->verifyReturn($this->_params)) {
            throws('支付宝快捷登录-验签失败');
        }

        // 支付宝自己定义的令牌
        // 其实并不是OAuth的令牌
        $accessTokenInfo = [
            'access_token' => $this->_params['token'],
            'expires_in'   => 86400000,
        ];

        // 用户完整信息
        $userInfo = [
            'nickname' => $this->_params['real_name'] ?: $this->_params['email'],
        ];

        return [
            'id'        => $this->_params['user_id'],
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
                'nickname' => $thirdUser['user_info']['nickname'],
            ]);
        }
    }
}