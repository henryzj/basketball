<?php

/**
 * 手机短信队列
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Queue_Sms extends Com_Queue_Abstract
{
    protected $_queueName = 'sms';

    // 处理从队列弹出的一个任务
    public function postPop($oneTask)
    {
        $oneTask = json_decode($oneTask, true);

        if (! $oneTask) {
            return [
                'is_ok'      => 0,
                'return_msg' => '空元素',
            ];
        }

        $smsClass = 'Com_Sms_' . $oneTask['adapter'];

        if (isset($oneTask['send_type']) && $oneTask['send_type'] == 'voice') {
            // 发送语音验证码
            $result = $smsClass::sendVoice($oneTask['params']);
        }
        else {
            // 发送纯文本短信
            $result = $smsClass::sendText($oneTask['params']);
        }

        return [
            'is_ok'      => $result['is_ok'],
            'return_msg' => $result['return_msg'],
        ];
    }
}