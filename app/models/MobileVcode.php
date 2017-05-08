<?php

/**
 * 手机-短信验证码
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_MobileVcode extends Core_Model_Abstract
{
    // 使用场景
    public static $SCENES = [
        'REGISTER'     => 1,  // 注册账号
        'RESET_PWD'    => 2,  // 重置密码
        'CHANGE_INFO'  => 3,  // 更换手机号（或其他重要信息）
        'LOGIN'        => 4,  // 动态登录
    ];

    const
        VCODE_UNIVERSAL = '130202',   // 万能验证码
        VCODE_INTERVAL  = 60,         // 验证码发送间隔（秒）
        VCODE_TTL       = 7200;       // 验证码有效期（秒）

    // 发送验证码
    public static function sendVcode($mobile, $scene, $sendType = 'text')
    {
        if (! $mobile || ! Com_Validate::mobile($mobile)) {
            throws('请输入正确的手机号');
        }

        if (! isset(self::$SCENES[$scene])) {
            throws('验证码使用场景未定义');
        }

        $sceneStr = $scene;

        $vcode = null;
        $scene = self::$SCENES[$scene];
        $vInfo = Dao('Core_MobileVcode')->get([$mobile, $scene]);

        if ($vInfo) {

            // 冷却检测
            $remainSecs = strtotime($vInfo['created_at']) + self::VCODE_INTERVAL - $GLOBALS['_TIME'];
            if ($remainSecs > 0) {
                throws('验证码发送太频繁，请' . $remainSecs . '秒后再试');
            }

            // 在有效期内（还是发送原来的验证码）
            if ($vInfo['expired_at'] > $GLOBALS['_DATE']) {
                $vcode = $vInfo['vcode'];
            }
        }

        if (! $vcode) {
            // 生成随机验证码
            $vcode = mt_rand(100000, 999999);
        }

        // 一小时内只能发出N次
        self::__checkLimit($mobile);

        $setArr = [
            'mobile'     => $mobile,
            'scene'      => $scene,
            'vcode'      => $vcode,
            'created_at' => $GLOBALS['_DATE'],
            'expired_at' => incrDate($GLOBALS['_DATE'], self::VCODE_TTL),
        ];

        if (! Dao('Core_MobileVcode')->replaceByPk($setArr, [$mobile, $scene])) {
            throws('验证码发送失败，请稍后再试');
        }

        // 发送语音验证码（支持4~6位阿拉伯数字）
        if ('voice' == $sendType) {
            Model_Sms::send($mobile, ['code' => $vcode], 'VCODE_' . $sceneStr, 'voice');
        }
        // 发送纯文本短信
        else {
            Model_Sms::send($mobile, ['code' => $vcode], 'VCODE_' . $sceneStr);
        }

        return true;
    }

    // 检测验证码
    public static function checkVcode($mobile, $scene, $vcode)
    {
        if (! isset(self::$SCENES[$scene])) {
            throws('验证码使用场景未定义');
        }

        // 万能验证码
        if (self::VCODE_UNIVERSAL && $vcode == self::VCODE_UNIVERSAL) {
            return true;
        }

        if (! $vcode) {
            throws('请填写验证码');
        }

        $scene = self::$SCENES[$scene];
        $vInfo = Dao('Core_MobileVcode')->get([$mobile, $scene]);

        if (! $vInfo) {
            throws('验证码信息不存在');
        }

        // 防刷机制：一段时间内最大错误次数
        $errTimesKey = 'checkVcodeErrTimes:' . date('YmdH') . ':' . $mobile . ':' . $scene;

        if (F('Memcache')->default->get($errTimesKey) >= 10) {
            throws('验证码输错超过最大次数，请稍后再试');
        }

        if ($vInfo['vcode'] != $vcode) {
            F('Memcache')->default->increment($errTimesKey);
            throws('验证码不正确，请重试');
        }

        if ($vInfo['expired_at'] < $GLOBALS['_DATE']) {
            throws('验证码已过期，请重新获取');
        }

        // 更新为已验证
        $setArr = [
            'checked_at' => $GLOBALS['_DATE'],
        ];

        return Dao('Core_MobileVcode')->updateByPk($setArr, [$mobile, $scene]);
    }

    // 一小时内只能发出N次
    private static function __checkLimit($mobile)
    {
        $LIMIT = 3;
        $PERIOD = 3600;

        // 1小时之内最多发三次
        $cacheKey = 'sendVcodeLimit:' . $mobile;
        $sInfo = F('Memcache')->default->get($cacheKey) ?: [];

        $count = 0;

        if ($sInfo) {
            foreach ($sInfo as $timestamp) {
                // 计算一小时之内的数量
                if ($GLOBALS['_TIME'] - $timestamp < $PERIOD) {
                    if ((++$count) >= $LIMIT) {
                        throws('验证码短信1小时内同一手机号发送次数不能超过3次');
                    }
                }
            }
        }

        $sInfo[] = $GLOBALS['_TIME'];
        $sInfo = array_slice($sInfo, -3);
        F('Memcache')->default->set($cacheKey, $sInfo);
    }
}