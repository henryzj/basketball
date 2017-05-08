<?php

/**
 * 企业付款队列（转账队列）
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Queue_MchPay extends Com_Queue_Abstract
{
    protected $_queueName = 'mchPay';

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

        // 发送冷却检测
        // 微信接口限制了同一人发送间隔必须大于X秒
        if ($this->__sendingCooldown($oneTask['recv_openid'])) {
            sleep(30);
        }

        $result = Model_Weixin_MchPay_Api::send(
            $oneTask['recv_openid'],
            $oneTask['money'],
            $oneTask['desc']
        );

        // 发送结果
        $isOk = ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') ? 1 : 0;

        if ($isOk) {
            // 为便于财务对账
            // 这里再额外记录发送成功的记录
            Com_Logger_Redis::custom('mchPayFinacial', [
                'action'      => $oneTask['action'],
                'recv_uid'    => $oneTask['recv_uid'],
                'recv_openid' => $oneTask['recv_openid'],
                'money'       => $oneTask['money'],
                'memos'       => $oneTask['memos'],
                'created_at'  => date('Y-m-d H:i:s'),    // 必须实时获取，因为队列是守护进程
            ]);
        }

        // 设置冷却间隔
        $this->__sendingCooldown($oneTask['recv_openid'], 30);

        return [
            'is_ok'      => $isOk,
            'return_msg' => $result,
        ];
    }

    // 发送冷却检测
    // 微信接口限制了同一人发送间隔必须大于X秒
    private function __sendingCooldown($openId, $ttl = null)
    {
        $redis = F('Redis')->queue;

        // 读
        if (null === $ttl) {
            return $redis->get('mchPaySendingCd:' . $openId);
        }
        // 写
        else {
            return $redis->setex('mchPaySendingCd:' . $openId, $ttl, 1);
        }
    }
}