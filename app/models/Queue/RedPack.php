<?php

/**
 * 红包队列
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Queue_RedPack extends Com_Queue_Abstract
{
    protected $_queueName = 'redPack';

    // 从队列弹出前的一些检测操作
    // 例如：红包队列每天0点~8点开始睡眠，不弹出处理任务（因为微信API规定这段时间禁止发红包）
    public function prePop()
    {
        while (1) {

            // 现在几点钟 0~13
            $hour = date('G');

            if ($hour >= 8) {
                break;
            }

            Com_Logger_File::info('redPackQueueSleepLog', date('Y-m-d H:i:s'));

            // 睡10分钟后再检查下
            sleep(600);
        }
    }

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

        // 红包发送冷却检测
        // 微信接口限制了同一人发送间隔必须大于X秒
        if ($this->__sendingCooldown($oneTask['recv_openid'])) {
            sleep(30);
        }

        $result = Model_Weixin_RedPack_Api::send(
            $oneTask['recv_openid'],
            $oneTask['title'],
            $oneTask['wishing'],
            $oneTask['money']
        );

        // 发送结果
        $isOk = ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') ? 1 : 0;

        if ($isOk) {
            // 为便于财务对账
            // 这里再额外记录发送成功的红包记录
            Com_Logger_Redis::custom('redPackFinacial', [
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

    // 红包发送冷却检测
    // 微信接口限制了同一人发送间隔必须大于X秒
    private function __sendingCooldown($openId, $ttl = null)
    {
        $redis = F('Redis')->queue;

        // 读
        if (null === $ttl) {
            return $redis->get('redPackSendingCd:' . $openId);
        }
        // 写
        else {
            return $redis->setex('redPackSendingCd:' . $openId, $ttl, 1);
        }
    }
}