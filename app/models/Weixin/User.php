<?php

/**
 * 微信-用户相关
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_User extends Core_Model_Abstract
{
    // 我方主动获取用户基本信息（包括UnionID机制）
    // 前提：用户已关注该公众号，成为我们粉丝后，我们才能拉取到他的用户信息
    public static function getFansUserInfo($openId)
    {
        $params = [
            'openid' => $openId,
            'lang'   => 'zh_CN',
        ];

        $url = 'https://api.weixin.qq.com/cgi-bin/user/info';
        $userInfo = Model_Weixin_Core::curlWithAccessToken($url, $params);

        // 过滤昵称中的 emoji 表情符号
        if (isset($userInfo['nickname'])) {
            $userInfo['nickname'] = Model_Weixin_Util::filterNickname($userInfo['nickname']);
        }

        if (! isset($userInfo['unionid'])) {
            $userInfo['unionid'] = $userInfo['openid'];
        }

        return $userInfo;
    }
}