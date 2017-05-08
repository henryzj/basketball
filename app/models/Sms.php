<?php

/**
 * 发送短信
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Sms extends Core_Model_Abstract
{
    // 使用哪种方式发送
    protected static $_adapter = 'YunPian';    // YunPian/AliDaYu/Juhe

    // 设置我的适配器
    public static function setAdapter($adapter)
    {
        self::$_adapter = $adapter;
    }

    public static function send($mobile, array $tplVars = [], $scene, $signName = null, $sendType = 'text')
    {
        if (! isset($GLOBALS['_SMS_TPL_CONFIG'][$scene])) {
            throws('短信场景未定义：' . $scene);
        }

        // 正常定义
        if (is_array($GLOBALS['_SMS_TPL_CONFIG'][$scene])) {
            $smsTplConfig = $GLOBALS['_SMS_TPL_CONFIG'][$scene];
        }
        // 克隆定义
        else {
            $smsTplConfig = $GLOBALS['_SMS_TPL_CONFIG'][$GLOBALS['_SMS_TPL_CONFIG'][$scene]];
        }

        $config = $smsTplConfig[self::$_adapter];

        $oneTask = [
            'adapter'   => self::$_adapter,
            'send_type' => $sendType,
            'params'    => [
                'mobile'  => $mobile,
                'tplId'   => isset($config['tplId']) ? $config['tplId'] : null,
                'tplVars' => $tplVars,
            ]
        ];

        if (! $signName) {
            $signName = SMS_SIGN_NAME;
        }

        switch (self::$_adapter) {

            case 'YunPian':

                $oneTask['params'] += [
                    'content' => '【' . $signName . '】' . $config['content']
                ];

                break;

            case 'AliDaYu':

                $oneTask['params'] += [
                    'signName'      => $signName,
                    'calledShowNum' => ALIDAYU_SMS_CALLED_NUM,
                ];

                // 验证码模板特殊变量
                $oneTask['params']['tplVars']['product'] = SMS_SIGN_NAME;

                // 语音模板
                if ($sendType == 'voice') {
                    $oneTask['params']['tplId'] = $config['tplIdVoice'];
                }

                break;
        }

        return S('Model_Queue_Sms')->push(json_encode($oneTask, JSON_UNESCAPED_UNICODE));
    }
}