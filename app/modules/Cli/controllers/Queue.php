<?php

/**
 * 队列守护进程
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Controller_Queue extends Core_Controller_Cli
{
    // 红包队列
    public function redPackAction()
    {
        Com_Queue::setDaemon(S('Model_Queue_RedPack'));
    }

    // 模板消息队列
    public function tplMsgAction()
    {
        Com_Queue::setDaemon(S('Model_Queue_TplMsg'));
    }

    // 邮件队列
    public function emailAction()
    {
        Com_Queue::setDaemon(S('Model_Queue_Email'));
    }

    // 短信队列
    public function smsAction()
    {
        Com_Queue::setDaemon(S('Model_Queue_Sms'));
    }

    // 转账队列
    public function mchPayAction()
    {
        Com_Queue::setDaemon(S('Model_Queue_MchPay'));
    }

    // 业务队列
    public function bizAction()
    {
        Com_Queue::setDaemon(S('Model_Queue_Biz'));
    }

    // 手动补救执行失败的队列元素
    // 将 log_queue 中执行失败的任务，弹出并重新push进队列重新排队执行
    public function retrieveAction()
    {
        $ids = $this->getx('ids');
        $ids = explode(',', $ids);

        $ok = Com_Queue::retrieve($ids);

        exit('DONE:' . $ok);
    }
}