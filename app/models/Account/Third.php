<?php

/**
 * 第三方通行证-模型工厂
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Account_Third extends Core_Model_Abstract
{
    /**
     * 平台类型定义
     *
     * @var array
     */
    public static $sources = [
        'Alipay'          => 1,  // 支付宝
        'Weixin_MP'       => 1,  // 微信公众号
        'Weixin_Native'   => 1,  // 微信原生APP
        'Weixin_Web'      => 1,  // 微信网页版
        'QQ_Web'          => 1,  // QQ互联（网页应用）
        'QQ_Native'       => 1,  // QQ互联（移动应用）
        'Weibo_Web'       => 1,  // 新浪微博（网页应用）
        'Weibo_Native'    => 1,  // 新浪微博（移动应用）
        'LinkedIn_Web'    => 1,  // 领英
    ];

    /**
     * 实例工厂
     *
     * @param string $source
     * @return Model_Passport_Abstract
     */
    public static function factory($source)
    {
        $source = ucfirst($source);

        if (! $source || ! isset(self::$sources[$source])) {
            throws('平台渠道不合法');
        }

        $className = 'Model_Account_Third_' . $source;

        return new $className($source);
    }

    /**
     * 是否第三方渠道平台注册的用户
     *
     * @param string $regSource 注册渠道
     * @return bool
     */
    public static function isThirdUser($regSource)
    {
        return isset(self::$sources[$regSource]) ? true : false;
    }

    // 获取我的微信openid
    // 默认是我在官方公众号下的openid
    public static function getWxOpenId($uid, $wxFrom = 'MP', $wxAppId = WX_MP_APP_ID)
    {
        if (! $wxUnionId = Dao('Ucenter_AccountThirdPfBind')->getThirdUid($uid, 'Weixin')) {
            return false;
        }

        $wxMpOpenId = Dao('Ucenter_WxUnion')->getField([$wxUnionId, 'Weixin_' . $wxFrom, $wxAppId], 'openid');

        return $wxMpOpenId;
    }

    // 根据微信openid查出我方uid
    public static function getUidByWxOpenId($openId)
    {
        $uniondId = Dao('Ucenter_WxUnion')->field('unionid')->where(['openid' => $openId])->fetchOne();

        if (! $uniondId) {
            return false;
        }

        $uid = Dao('Ucenter_AccountThirdPfBind')->getField(['Weixin', $uniondId], 'uid');

        return $uid;
    }

    // 更新粉丝对公众号的关注状态
    public static function setWxMpSubscribed($wxMpOpenId, $status, $sceneId = 0)
    {
        // 非首次关注
        if (Dao('Ucenter_WxMpFollow')->get([$wxMpOpenId, WX_MP_APP_ID])) {

            $setArr = [
                'status'     => $status ? 1 : 0,
                'updated_at' => $GLOBALS['_DATE'],
            ];

            return Dao('Ucenter_WxMpFollow')->updateByPk($setArr, [$wxMpOpenId, WX_MP_APP_ID]);
        }

        // 首次关注
        else {

            $setArr = [
                'openid'     => $wxMpOpenId,
                'app_id'     => WX_MP_APP_ID,
                'status'     => $status ? 1 : 0,
                'scene_id'   => $sceneId,
                'updated_at' => $GLOBALS['_DATE'],
            ];

            if (! Dao('Ucenter_WxMpFollow')->insert($setArr)) {
                return false;
            }

            // 发放“首次关注”红包
            if (1 == $status) {
                Model_Weixin_RedPack_Biz::firstSubscribe($wxMpOpenId);
            }

            return true;
        }
    }

    // 主动拉取、更新用户信息
    // 微信公众号用户专用：去微信公众平台拉取一遍用户昵称和头像
    public static function refreshWxFollowInfo($uid)
    {
        if (! $wxMpOpenId = self::getWxOpenId($uid, 'MP')) {
            return false;
        }

        $isSubscribed = Dao('Ucenter_WxMpFollow')->getField([$wxMpOpenId, WX_MP_APP_ID], 'status');

        // 必须先关注我们公众号，我们才能主动拉取到该用户的信息
        if (! $isSubscribed) {
            return false;
        }

        // 主动去微信接口主动拉取一遍用户信息
        $wxFansUserInfo = Model_Weixin_User::getFansUserInfo($wxMpOpenId);

        if (! $wxFansUserInfo) {
            return false;
        }

        // 对方已取关，所以拉不到详细信息了，直接跳过
        if (0 == $wxFansUserInfo['subscribe']) {
            // 更新我方记录的“取关”状态
            self::setWxMpSubscribed($wxMpOpenId, 0);
            return false;
        }

        $setArr = [
            'nickname'   => $wxFansUserInfo['nickname'],
            'sex'        => $wxFansUserInfo['sex'],
            'province'   => $wxFansUserInfo['province'],
            'city'       => $wxFansUserInfo['city'],
            'country'    => $wxFansUserInfo['country'],
            'language'   => $wxFansUserInfo['language'],
            'headimgurl' => $wxFansUserInfo['headimgurl'],
        ];

        // 更新“用户中心”的用户信息表
        Dao('Ucenter_AccountInfo')->updateByPk($setArr, $uid);

        // 更新“本应用”的用户信息表
        Dao('Core_UserIndex')->updateByPk($setArr, $uid);

        return true;
    }
}