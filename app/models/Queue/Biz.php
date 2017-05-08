<?php

/**
 * 通用业务延迟队列
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Queue_Biz extends Com_Queue_Abstract
{
    protected $_queueName = 'biz';

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

        $result = call_user_func_array($oneTask['callback'], $oneTask['params']);

        return [
            'is_ok'      => 1,
            'return_msg' => isset($result['message']) ? $result['message'] : 'OK',
        ];
    }
}
