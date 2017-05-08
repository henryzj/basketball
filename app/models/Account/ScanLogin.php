<?php

/**
 * 电脑网页上扫码登录
 * 公众号带参数的临时二维码
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Account_ScanLogin extends Core_Model_Abstract
{
    const SCENE_ID_XKEY = 'xiaozz';
    const SCENE_ID_TTL  = 86400;

    private static function getRedis()
    {
        return F('Redis')->default;
    }

    // 构造场景ID签名
    public static function verifySign($sceneId, $timestamp, $sceneSign)
    {
        if (! $sceneId || ! $timestamp || ! $sceneSign) {
            return false;
        }

        return self::buildSign($sceneId, $timestamp) == $sceneSign ? true : false;
    }

   // 构造场景ID签名
    public static function buildSign($sceneId, $timestamp)
    {
        return sha1($sceneId . '|' . $timestamp . '|' . self::SCENE_ID_XKEY);
    }

    // 验证场景ID有效性
    public static function verifySceneId($sceneId, $timestamp, $sceneSign)
    {
        if (! self::verifySign($sceneId, $timestamp, $sceneSign)) {
            throws('场景ID验签失败');
        }

        // 临时二维码已过期
        if ($timestamp + self::SCENE_ID_TTL < $GLOBALS['_TIME']) {
            F('Session')->del('scanLoginQrCodeSceneId');
            throws('QRCODE_EXPIRED');
        }

        return true;
    }

    // 创建一个带参数临时二维码
    // 登录原理：
    //    使用手机微信扫码后，在我方接收的 onScan 事件中将场景ID与用户UID绑定
    //    网页上JS轮询检测，以此实现微信用户在电脑网页上登录
    public static function createQrCode()
    {
        // 缓存到session中防止同一个人反复F5刷页面把场景ID全占了
        $result = F('Session')->get('scanLoginQrCodeSceneId');

        if (! $result) {

            // 找出一个可用的场景ID
            while (1) {

                // 约定临时码场景ID范围“20亿+”为扫码登录使用
                $sceneId = mt_rand(2000000000, 2147483647);

                if (! self::getRedis()->exists('scanLoginQrCodeSceneId:' . $sceneId)) {
                    self::getRedis()->setex('scanLoginQrCodeSceneId:' . $sceneId, self::SCENE_ID_TTL, -1);
                    break;
                }
            }

            $result = [
                'scene_id'   => $sceneId,
                'timestamp'  => $GLOBALS['_TIME'],
                'scene_sign' => self::buildSign($sceneId, $GLOBALS['_TIME']),   // 生成场景ID签名、防止前台随意篡改
                'img_url'    => Model_Weixin_QrCode::create(0, $sceneId),       // 正式创建二维码
            ];

            F('Session')->set('scanLoginQrCodeSceneId', $result);
        }

        return $result;
    }

    // 获取跟指定场景ID对应的UID
    // 绑定关系是在通过手机扫码后，微信通知我方并触发 onScan 事件中建立
    public static function getScanLoginUid($sceneId)
    {
        $uid = self::getRedis()->get('scanLoginQrCodeSceneId:' . $sceneId);

        if (! $uid) {
            F('Session')->del('scanLoginQrCodeSceneId');
            throws('QRCODE_EXPIRED');
        }

        // 尚未检测到绑定（用户还未扫码）
        if ($uid == -1) {
            throws('NO_BIND');
        }

        // 使用过后即解除绑定关系，尽快释放场景ID让给别人用
        self::getRedis()->del('scanLoginQrCodeSceneId:' . $sceneId);
        F('Session')->del('scanLoginQrCodeSceneId');

        return $uid;
    }

    // 建立场景ID和UID的绑定关系（X分钟内有效）
    public static function bindSceneIdToUid($sceneId, $uid)
    {
        // 如果已过期，则不继续绑定了
        if (! self::getRedis()->exists('scanLoginQrCodeSceneId:' . $sceneId)) {
            return false;
        }

        return self::getRedis()->setex('scanLoginQrCodeSceneId:' . $sceneId, 600, $uid);
    }
}