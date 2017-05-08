<?php

/**
 * 邮件队列
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Queue_Email extends Com_Queue_Abstract
{
    protected $_queueName = 'email';

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

        // 文件附件
        $attachmentPath = isset($oneTask['files']) ? $oneTask['files'] : null;

        $mailerClass = 'Com_Mailer_' . ucfirst($oneTask['adapter']);

        // 正式发送
        $result = $mailerClass::send($oneTask);

        return [
            'is_ok'      => $result['is_ok'],
            'return_msg' => $result['return_msg'],
        ];
    }
}