<?php

/**
 * 微信-上传下载多媒体文件
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_Media extends Core_Model_Abstract
{
    // TODO
    // image|voice|video|thumb
    public static function upload($type, $media)
    {
        $params = [
            'type'  => $type,
            'media' => $media,
        ];

        $url = 'http://file.api.weixin.qq.com/cgi-bin/media/upload';
        $result = Model_Weixin_Core::curlWithAccessToken($url, $params);

        return $result;
    }

    public static function download($mediaId)
    {
        $params = ['media_id' => $mediaId];
        $url    = 'http://file.api.weixin.qq.com/cgi-bin/media/get';

        $result = Model_Weixin_Core::curlWithAccessToken($url, $params, false, 'STREAM');

        return $result;
    }

    public static function getDownloadUrl($mediaId)
    {
        // TODO 保证可用性
        $accessToken = Model_Weixin_Core::getAccessToken();

        $url = 'http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=' . $accessToken . '&media_id=' . $mediaId;

        return $url;
    }

    // 将微信端照片转存到七牛
    public static function transToQiniu($serverMediaId, $method = 1, $bucket = QINIU_DEF_BUCKET)
    {
        // 方式1：远程直接抓取（微信->七牛）
        if (1 == $method) {

            // 拼接微信端照片URL
            $remoteUrl = self::getDownloadUrl($serverMediaId);

            // 七牛直接远程抓取并保存在云端
            $imgKey = Com_Qiniu::fetchSaveRemote($remoteUrl, $bucket);
        }

        // 方式2：从本地中转（微信->本地->七牛）
        else {

            // 从微信平台下载到本地
            // 目前多媒体文件下载接口的频率限制为10000次/天
            $imgStream = self::download($serverMediaId);

            // 转存到七牛云存储
            $imgKey = Com_Qiniu::uploadByStream($imgStream, $bucket);
        }

        return $imgKey;
    }
}