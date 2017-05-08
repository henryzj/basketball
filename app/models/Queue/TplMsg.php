<?php

/**
 * 模板消息队列
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Queue_TplMsg extends Com_Queue_Abstract
{
    protected $_queueName = 'tplMsg';

    // 处理从队列弹出的一个任务
    public function postPop($oneTask)
    {
        $msgData = $oneTask;

        if (! $msgData) {
            return [
                'is_ok'      => 0,
                'return_msg' => '空元素',
            ];
        }

        $result = Model_Weixin_TplMsg_Api::send($msgData);

        // 发送结果
        $isOk = ($result['errcode'] == 0 && isset($result['msgid'])) ? 1 : 0;

        return [
            'is_ok'      => $isOk,
            'return_msg' => $result,
        ];
    }
}