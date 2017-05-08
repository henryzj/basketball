<?php

abstract class Model_Weixin_Recv_Abstract extends Core_Model_Abstract
{
    // 接收到的请求数据
    protected $_data = [];

    public function __construct(array $data)
    {
        $this->_data = $data;
    }

    abstract public function process();

    // 回复文本消息
    public function replyText($content)
    {
        $msg = [
            'Content' => $content
        ];

        $this->_replyData($msg, 'text');
    }

    // 回复图片消息
    public function replyImage($mediaId)
    {
        $msg = [
            'Image' => [
                'MediaId' => $mediaId,
            ],
        ];

        $this->_replyData($msg, 'image');
    }

    // 回复语音消息
    public function replyVoice($mediaId)
    {
        $msg = [
            'Voice' => [
                'MediaId' => $mediaId,
            ],
        ];

        $this->_replyData($msg, 'voice');
    }

    // 回复视频消息
    public function replyVideo($mediaId, $title = '', $description = '')
    {
        $msg = [
            'Video' => [
                'MediaId'     => $mediaId,
                'Title'       => $title,
                'Description' => $description,
            ],
        ];

        $this->_replyData($msg, 'video');
    }

    // 回复音乐消息
    public function replyMusic($mediaId, $title = '', $description = '', $musicUrl = '', $hQMusicUrl = '')
    {
        $msg = [
            'Music' => [
                'MediaId'      => $mediaId,
                'Title'        => $title,
                'Description'  => $description,
                'MusicURL'     => $musicUrl,    // 音乐链接
                'HQMusicUrl'   => $hQMusicUrl,  // 高质量音乐链接
                'ThumbMediaId' => $mediaId,
            ],
        ];

        $this->_replyData($msg, 'music');
    }


    // 回复图文消息
    public function replyNews(array $articles)
    {
        /*
            格式如下：
            $articles = [
                [
                    'Title'       => '',
                    'Description' => '',
                    'PicUrl'      => '',
                    'Url'         => '',
                ],
                [
                    'Title'       => '',
                    'Description' => '',
                    'PicUrl'      => '',
                    'Url'         => '',
                ],
            ];
        */

        $msg = [
            'Articles'     => $articles,
            'ArticleCount' => count($articles),
        ];

        $this->_replyData($msg, 'news');
    }

    // 发送被动回复消息给微信平台
    // 对于每一个POST请求，开发者在响应包（Get）中返回特定XML结构，对该消息进行响应
    // 现支持回复文本、图片、图文、语音、视频、音乐。
    // 请注意，回复图片等多媒体消息时需要预先上传多媒体文件到微信服务器。
    protected function _replyData(array $msg, $msgType = 'Text')
    {
        $msg += [
            'ToUserName'   => $this->_data['FromUserName'],
            'FromUserName' => $this->_data['ToUserName'],
            'CreateTime'   => $GLOBALS['_TIME'],
            'MsgType'      => strtolower($msgType),
        ];

        $xmlStr = Helper_String::arrayToXml($msg);

        // 微信收发日志
        addWeixinLog($xmlStr, 1);

        echo $xmlStr;
        exit();
    }
}