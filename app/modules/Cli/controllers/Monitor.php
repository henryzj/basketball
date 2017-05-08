<?php

/**
 * 监控任务
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Controller_Monitor extends Core_Controller_Cli
{
    // 每1小时检测1次队列中是否有失败任务
    public function scanFailedLogQueueAction()
    {
        $hoursBefore = $this->getInt('hours') ?: 1;
        $beginTime = date('Y-m-d H:i:s', time() - $hoursBefore * 3600 * 12);

        $html = $this->__getFailedLogsHtml($beginTime);

        if (! $html) {
            exit('No failed record in log_queue.');
        }

        // 仅需要输出显示即可
        if ($this->getBool('print')) {
            exit($html);
        }

        // 如果需要邮件报警
        $receivers = [
            'op@morecruit.cn',
        ];

        $result = Model_Mailer::sendDirect($receivers, $GLOBALS['SITE_HOST'] . ' 队列出现失败记录', $html);

        vd($result);
    }

    private function __getFailedLogsHtml($beginTime)
    {
        // 找出1小时内执行失败的队列任务
        $whereSql = "`is_ok` = 0 AND `created_at` > '{$beginTime}'";
        $logList = Dao('Massive_LogQueue')->where($whereSql)->limit(50)->fetchAll();

        // 没有失败记录
        if (! $logList) {
            return null;
        }

        $html = '';

        foreach ($logList as $log) {
            if (stripos($log['return_msg'], '43004-require subscribe') === false) {
                $fixUrl = sUrl('/cli/queue/retrieve/ids/');
                $html .= "id: {$log['id']} <a href='{$fixUrl}{$log['id']}'>[重试]</a><br />
                    model_name: {$log['model_name']} <br />
                    org_infos: {$log['org_infos']} <br />
                    return_msg: {$log['return_msg']} <br />
                    created_at: {$log['created_at']} <br /><br />
                    ======================================= <br /><br />";
            }
        }

        return $html;
    }

    // 重试补救队列的失败任务
    public function retieveAllAction()
    {
        $hoursBefore = $this->getInt('hours') ?: 1;
        $beginTime = date('Y-m-d H:i:s', time() - $hoursBefore * 3600);

        // 找出1小时内执行失败的队列任务
        $whereSql = "`is_ok` = 0 AND `created_at` > '{$beginTime}'";
        $ids = Dao('Massive_LogQueue')->where($whereSql)->limit(50)->fetchPks();

        if (! $ids) {
            exit('nothing to retrieve');
        }

        // 批量重试
        $ok = Com_Queue::retrieve($ids);

        exit('DONE:' . $ok);
    }

    // 监控扫描指定表是否有新记录产生
    public function scanNewDataListAction()
    {
        $moniterTbls = [
            DB_PREFIX . 'massive.log_common',
            DB_PREFIX . 'core.v2_feedback',
        ];

        $db = Com_DB::get(DB_PREFIX . 'massive');

        $tblPos = $db->fetchPairs("SELECT tbl_name, last_max_id FROM `log_monitor`");

        $ok = [];

        // 邮件内容
        $html = '';

        foreach ($moniterTbls as $tblName) {

            $lastMaxId = isset($tblPos[$tblName]) ? $tblPos[$tblName] : 0;

            if ($dataList = $this->__getNewDataList($tblName, $lastMaxId)) {

                $subHtml = '';

                foreach ($dataList as $row) {
                    $subHtml .= '<pre>' . print_r($row, true) . '</pre>';
                    $subHtml .= "======================================= <br />";
                }

                $html .= '<h1>' . $tblName . '</h1>' . $subHtml;

                // 标记本次位置
                $db->query("REPLACE INTO `log_monitor` (`tbl_name`, `last_max_id`, `updated_at`)
                    VALUES ('{$tblName}', '{$dataList[0]['id']}', '{$GLOBALS['_DATE']}') ");

                $ok[$tblName] = count($dataList);
            }
        }

        if (! $html) {
            exit('no new monitor records');
        }

        // 仅需要输出显示即可
        if ($this->getBool('print')) {
            exit($html);
        }

        // 如果需要邮件报警
        $result = Model_Mailer::sendDirect(['op@morecruit.cn'], $GLOBALS['SITE_HOST'] . ' 监控表产生新记录', $html);

        pr($ok);
    }

    private function __getNewDataList($tblName, $lastMaxId = 0)
    {
        $tmp = explode('.', $tblName);
        if (count($tmp) == 1) {
            $dbName = DB_PREFIX . 'core';
            $tblName = $tmp[1];
        }
        else {
            $dbName = $tmp[0];
            $tblName = $tmp[1];
        }

        $db = Com_DB::get($dbName);

        return $db->fetchAll("SELECT * FROM `{$tblName}` WHERE `id` > '{$lastMaxId}' ORDER BY `id` DESC");
    }

    // 定期检测各队列是否出现阻塞、堆积
    public function checkQueueBlockAction()
    {
        $queueNames = ['Biz', 'Email', 'MchPay', 'RedPack', 'Sms', 'TplMsg'];
        $baseUrl = 'http://' . $GLOBALS['PP_SITE_HOST'] . '/test/tools/viewQueue';

        $counts = [];

        foreach ($queueNames as $queueName) {
            if ($json = Com_Http::request($baseUrl, ['name' => $queueName])) {
                $data = json_decode($json, true);
                $count = count($data);
                if ($count > 0) {
                    $counts[$queueName] = $count;
                }
            }
        }

        if ($counts) {
            Model_Mailer::sendDirect(['op@morecruit.cn'], $GLOBALS['PP_SITE_HOST'] . ' 队列出现阻塞', json_encode($counts));
        }

        pr($counts);
    }
}