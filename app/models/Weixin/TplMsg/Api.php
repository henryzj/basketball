<?php

/**
 * 微信模板消息-通信接口类
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_TplMsg_Api extends Core_Model_Abstract
{
    // API接口地址
    const REMOTE_API_URL = 'https://api.weixin.qq.com/cgi-bin/message/template/send';

    public static function send($msgData)
    {
        if (is_array($msgData)) {
            $msgData = json_encode($msgData);
        }

        // 向网关发起正式请求
        $result = Model_Weixin_Core::curlWithAccessToken(self::REMOTE_API_URL, $msgData);

        // 顺便记录下原始请求参数
        $result['_request_msgdata'] = $msgData;

        return $result;
    }

    public static function sendByParams(array $params)
    {
        $msgData = self::buildMsgData($params);

        return self::send($msgData);
    }

    public static function buildMsgData(array $params, $tplStyle = 'default')
    {
        $recvOpenId = $params['recv_openid'];
        $title      = $params['title'];
        $tplId      = $params['tpl_id'];
        $url        = $params['url'];
        $keywords   = isset($params['keywords']) ? $params['keywords'] : [];
        $remark     = isset($params['remark']) ? $params['remark'] : '';

        // 根据常量代号获取真正的模板ID
        if (isset($GLOBALS['WX_TPL_MSG_ID'][$tplId])) {
            $tplId = $GLOBALS['WX_TPL_MSG_ID'][$tplId];
        }

        switch ($tplStyle) {

            case 'credit':

                $msgData = [
                    'touser'      => $recvOpenId,
                    'template_id' => $tplId,
                    'url'         => $url,
                    'topcolor'    => '#0099FF',
                    'data'        => [
                        'first' => [
                            'value' => $title . PHP_EOL,
                            'color' => '#000000',
                        ],
                        'FieldName' => [
                            'value' => isset($keywords[0]) ? $keywords[0] : '',
                            'color' => '#000000',
                        ],
                        'Account' => [
                            'value' => isset($keywords[1]) ? $keywords[1] : '',
                            'color' => '#000000',
                        ],
                        'change' => [
                            'value' => isset($keywords[2]) ? $keywords[2] : '',
                            'color' => '#000000',
                        ],
                        'CreditChange' => [
                            'value' => isset($keywords[3]) ? $keywords[3] : '',
                            'color' => '#000000',
                        ],
                        'CreditTotal' => [
                            'value' => isset($keywords[4]) ? $keywords[4] : '',
                            'color' => '#000000',
                        ],
                        'Remark' => [
                            'value' => PHP_EOL . $remark . PHP_EOL,
                            'color' => '#0099FF',
                        ],
                    ],
                ];

                break;

            case 'hotel_order_reconfirm':

                $msgData = [
                    'touser'      => $recvOpenId,
                    'template_id' => $tplId,
                    'url'         => $url,
                    'topcolor'    => '#0099FF',
                    'data'        => [
                        'first' => [
                            'value' => $title . PHP_EOL,
                            'color' => '#000000',
                        ],
                        'hotelName' => [
                            'value' => isset($keywords[0]) ? $keywords[0] : '',
                            'color' => '#000000',
                        ],
                        'roomName' => [
                            'value' => isset($keywords[1]) ? $keywords[1] : '',
                            'color' => '#000000',
                        ],
                        'date' => [
                            'value' => isset($keywords[2]) ? $keywords[2] : '',
                            'color' => '#000000',
                        ],
                        'remark' => [
                            'value' => PHP_EOL . $remark . PHP_EOL,
                            'color' => '#0099FF',
                        ],
                    ],
                ];

                break;

            case 'hotel_order_received':

                $msgData = [
                    'touser'      => $recvOpenId,
                    'template_id' => $tplId,
                    'url'         => $url,
                    'topcolor'    => '#0099FF',
                    'data'        => [
                        'first' => [
                            'value' => $title . PHP_EOL,
                            'color' => '#000000',
                        ],
                        'OrderSn' => [
                            'value' => isset($keywords[0]) ? $keywords[0] : '',
                            'color' => '#000000',
                        ],
                        'OrderStatus' => [
                            'value' => isset($keywords[1]) ? $keywords[1] : '',
                            'color' => '#000000',
                        ],
                        'remark' => [
                            'value' => PHP_EOL . $remark . PHP_EOL,
                            'color' => '#0099FF',
                        ],
                    ],
                ];

                break;

            case 'default':
            default:

                $msgData = [
                    'touser'      => $recvOpenId,
                    'template_id' => $tplId,
                    'url'         => $url,
                    'topcolor'    => '#0099FF',
                    'data'        => [
                        'first' => [
                            'value' => $title . PHP_EOL,
                            'color' => '#000000',
                        ],
                        'keyword1' => [
                            'value' => '',
                            'color' => '#000000',
                        ],
                        'keyword2' => [
                            'value' => '',
                            'color' => '#000000',
                        ],
                        'keyword3' => [
                            'value' => '',
                            'color' => '#000000',
                        ],
                        'keyword4' => [
                            'value' => '',
                            'color' => '#000000',
                        ],
                        'remark' => [
                            'value' => PHP_EOL . $remark . PHP_EOL,
                            'color' => '#0099FF',
                        ],
                    ],
                ];

                $no = 1;
                foreach ($keywords as $keyword) {
                    $msgData['data']['keyword' . ($no++)] = [
                        'value' => $keyword,
                        'color' => '#000000',
                    ];
                }

                break;
        }

        return $msgData;
    }
}