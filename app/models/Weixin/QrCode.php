<?php

/**
 * 微信-二维码相关
 *
 * @link http://mp.weixin.qq.com/wiki/18/167e7d94df85d8389df6c94a7a8f78ba.html
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_QrCode extends Core_Model_Abstract
{
    /**
     * 创建带场景的二维码
     * 返回二维码图片URL地址
     *
     * @param  int $qrcodeType  0:临时 1:永久
     * @param  int $sceneId     场景值ID，临时二维码时为32位非0整型，永久二维码时最大值为100000（目前参数只支持1--100000）
     * @param  string $sceneStr 场景值ID（字符串形式的ID），字符串类型，长度限制为1到64，仅永久二维码支持此字段
     * @return string
     */
    public static function create($qrcodeType, $sceneId, $sceneStr = '')
    {
        // 临时二维码
        if ($qrcodeType == 0) {
            $params = [
                'action_name' => 'QR_SCENE',
                'action_info' => [
                    'scene' => [
                        'scene_id' => $sceneId
                    ]
                ],
                'expire_seconds' => 2592000, // 微信规定最长30天
            ];
        }
        // 永久二维码
        else {
            // 字符串形式场景ID
            if ($sceneStr) {
                $params = [
                    'action_name' => 'QR_LIMIT_STR_SCENE',
                    'action_info' => [
                        'scene' => [
                            'scene_str' => $sceneStr
                        ]
                    ]
                ];
            }
            // 数字形式场景ID
            else {
                $params = [
                    'action_name' => 'QR_LIMIT_SCENE',
                    'action_info' => [
                        'scene' => [
                            'scene_id' => $sceneId
                        ]
                    ]
                ];
            }
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create';
        $result = Model_Weixin_Core::curlWithAccessToken($url, json_encode($params));

        if (! isset($result['ticket'])) {
            throw new Model_Weixin_Exception($result['errcode'], $result['errmsg']);
        }

        $imgUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . $result['ticket'];

        return $imgUrl;
    }
}