<?php

/**
 * 七牛云存储
 * 核心方法集合
 *
 * @author JiangJian <silverd@sohu.com>
 */

if (! defined('QINIU_ACCESS_KEY')) {
    Yaf_Loader::import(CONF_PATH . 'cloud.php');
}

Yaf_Loader::import(SYS_PATH . 'vendor/autoload.php');

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;
use Qiniu\Processing\PersistentFop;

class Com_Qiniu
{
    // 将指定空间内的资源分批列出
    public static function listFiles($bucket = QINIU_DEF_BUCKET, $marker = null, $limit = 1000)
    {
        $auth = new Auth(QINIU_ACCESS_KEY, QINIU_SECRET_KEY);

        $bucketMgr = new BucketManager($auth);
        list($items, $marker, $err) = $bucketMgr->listFiles($bucket, null, $marker, $limit, null);

        if ($err !== null) {
            throws($err->message());
        }

        return [
            'items'  => $items,     // 所有返回条目的数组。如没有剩余条目则为空数组。
            'marker' => $marker,    // 下次列举的位置标记。如果没有剩余条目则返回空字符串。
        ];
    }

    // 直接抓取远程图片并存到云端
    public static function fetchSaveRemote($url, $bucket = QINIU_DEF_BUCKET, $fileName = null)
    {
        $auth = new Auth(QINIU_ACCESS_KEY, QINIU_SECRET_KEY);

        $bucketMgr = new BucketManager($auth);
        list($ret, $err) = $bucketMgr->fetch($url, $bucket, $fileName);

        if ($err !== null) {
            throws($err->message());
        }

        // 返回元素据key
        return $ret['key'];
    }

    // 上传单个二进制流到七牛
    public static function uploadByStream($stream, $bucket = QINIU_DEF_BUCKET)
    {
        $auth = new Auth(QINIU_ACCESS_KEY, QINIU_SECRET_KEY);

        $token = $auth->uploadToken($bucket);

        $uploadMgr = new UploadManager();

        list($ret, $err) = $uploadMgr->put($token, null, $stream);

        if ($err !== null) {
            throws($err->message());
        }

        // 返回元素据key
        return $ret['key'];
    }

    // 上传单个文件到七牛
    public static function uploadByFile($filePath, $bucket = QINIU_DEF_BUCKET, $fileName = null)
    {
        $auth = new Auth(QINIU_ACCESS_KEY, QINIU_SECRET_KEY);

        $token = $auth->uploadToken($bucket);

        $uploadMgr = new UploadManager();

        list($ret, $err) = $uploadMgr->putFile($token, $fileName, $filePath);

        if ($err !== null) {
            throws($err->message());
        }

        // 返回元素据key
        return $ret['key'];
    }

    // 删除七牛空间里的单个文件
    public static function deleteSingle($key, $bucket = QINIU_DEF_BUCKET)
    {
        $auth = new Auth(QINIU_ACCESS_KEY, QINIU_SECRET_KEY);

        $bucketMgr = new BucketManager($auth);
        $err = $bucketMgr->delete($bucket, $key);

        if ($err !== null) {
            throws($err->message());
        }

        return true;
    }

    public static function getThumbUrl($orgImgUrl, $w = 750, $h = 750, $quality = 100)
    {
        $con = strpos($orgImgUrl, '?') === false ? '?' : rawurlencode('|');

        return $orgImgUrl . $con . 'imageView2/1/w/' . $w . '/h/' . $h . '/interlace/1/quality/' . $quality;
    }

    // 高斯模糊
    public static function getBlurImgUrl($orgImgUrl, $radius = 30, $sigma = 10, $quality = 100)
    {
        if (! $orgImgUrl) {
            return null;
        }

        $con = strpos($orgImgUrl, '?') === false ? '?' : rawurlencode('|');

        return $orgImgUrl . $con . 'imageMogr2/blur/' . $radius . 'x' . $sigma . '/interlace/1/quality/' . $quality;
    }

    // 给原图增加文字水印
    public static function getWatermarkUrl($orgImgUrl, $text)
    {
        $con = strpos($orgImgUrl, '?') === false ? '?' : rawurlencode('|');

        return $orgImgUrl . $con . 'watermark/2/text/' . \Qiniu\base64_urlSafeEncode($text)
                          . '/font/' . \Qiniu\base64_urlSafeEncode('微软雅黑')
                          . '/fontsize/700/fill/' . \Qiniu\base64_urlSafeEncode('#FFFFFF')
                          . '/dissolve/85/gravity/SouthEast/dx/20/dy/20';
    }

    // 获取私有空间的下载链接
    public static function getPrivateDownloadUrl($baseUrl, $expires = 3600)
    {
        $auth = new Auth(QINIU_ACCESS_KEY, QINIU_SECRET_KEY);

        return $auth->privateDownloadUrl($baseUrl, $expires);
    }

    // 将本地文件转存到七牛
    public static function transLocalToQiniu($fileInputName, $bucket = QINIU_DEF_BUCKET, $allowedExts = '*', $maxSize = 0)
    {
        $fileKey = '';

        if (isset($_FILES[$fileInputName]['name']) && $_FILES[$fileInputName]['name']) {

            $uploader = new Com_Upload([
                'allowedExts' => $allowedExts,  // 允许后缀（半角逗号分隔）
                'maxSize'     => $maxSize,      // 尺寸限制（单位：字节）
            ]);

            // 临时上传到本地
            if (! $uploader->checkInput($fileInputName)) {
                throws('上传附件失败，请重试！' . $uploader->error()[0]);
            }

            $fileName = $uploader->getFinalSaveName($fileInputName);

            $fileKey = self::uploadByFile($_FILES[$fileInputName]['tmp_name'], $bucket, $fileName);
        }

        return $fileKey;
    }

    // 生成上传TOKEN
    public static function getUploadToken(array $custParams = [], $bucket = QINIU_DEF_BUCKET, $ttl = 7200, $callbackUrl = null)
    {
        $policy = [
            'fsizeLimit' => 10* 1024 * 1024,  // 最大10M
            'mimeLimit'  => 'image/*',        // 只允许传图片
        ];

        // 需要回调
        if ($callbackUrl) {

            // 魔术变量
            $callbackBody = [
                'key'   => '$(key)',
                'fname' => '$(fname)',
            ];

            // 自定义变量
            foreach ($custParams as $key => $value) {
                $callbackBody['x:' . $key] = $value;
            }

            $policy += [
                'callbackUrl'  => $callbackUrl,
                'callbackBody' => json_encode($callbackBody),
            ];
        }

        $auth = new Auth(QINIU_ACCESS_KEY, QINIU_SECRET_KEY);

        $upToken = $auth->uploadToken($bucket, null, $ttl, $policy);

        return $upToken;
    }

    // 异步响应上传回调
    public static function respCallback($callbackUrl)
    {
        $auth = new Auth(QINIU_ACCESS_KEY, QINIU_SECRET_KEY);

        $body = file_get_contents('php://input');

        if (! $auth->verifyCallback(
            'application/x-www-form-urlencoded',
            $_SERVER['HTTP_AUTHORIZATION'],
            $callbackUrl,
            $body
        )) {
            return ['ret' => 'fail'];
        }

        $body = json_decode($body, true);

        return [
            'key' => $body['key'],
        ];
    }

    // 裁剪url
    public static function cutImgUrl($key, $bucket, $fops, $prefix = '')
    {
        $auth = new Auth(QINIU_ACCESS_KEY, QINIU_SECRET_KEY);

        $persistentFop = new PersistentFop($auth, $bucket);

        $res = $persistentFop->execute($prefix . '/' . $key, $fops);

        return $res[0];
    }

    // 主动查询持久化状态
    public static function getFopStatus($persistentId)
    {
        $res = PersistentFop::status($persistentId);

        // 成功
        if (isset($res[0]['code']) && $res[0]['code'] == 0) {
            return [
                'fop_status' => 1,
                'input_key'  => $res[0]['inputKey'],
                'key'        => $res[0]['items'][0]['key'],
            ];
        } else {
            return [
                'fop_status' => 0,
            ];
        }
    }
}