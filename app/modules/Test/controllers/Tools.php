<?php

// 常用的内部工具
class Controller_Tools extends Core_Controller_Test
{
    public $yafAutoRender = false;

    // 清除全服 Memcache
    public function clearMcAction()
    {
        // 所有配置的Memcache分组
        $cacheGroups = Com_Cache::getGroups('Memcache');

        foreach ($cacheGroups as $group) {
            echo $group . ':' . (F('Memcache')->{$group}->flush() ? 'OK' : 'FAIL') . PHP_EOL;
        }
    }

    // 查看大C配置表内容
    public function viewConfigAction()
    {
        if (! $name = $this->getx('name')) {
            exit('plz input config key');
        }

        $this->json(C($name));
    }

    // 修改大C配置表内容
    public function setConfigAction()
    {
        if (! $name = $this->getx('name')) {
            exit('plz input config key');
        }

        $value = $this->getx('value');

        // 执行存储
        C($name, $value);

        $this->json(C($name));
    }

    // 查看指定队列数据
    public function viewQueueAction()
    {
        if (! $queueName = $this->getx('name')) {
            exit('plz input queue name');
        }

        $queue = S('Model_Queue_' . ucfirst($queueName));

        $this->json($queue->view());
    }

    // 查看中转日志表内容
    public function viewRedisLogAction()
    {
        $logName = $this->getx('name');

        if (! $logName) {
            exit('plz input log name');
        }

        $redis = F('Redis')->logs;

        $logName = Com_Logger_Redis::KEY_PREFIX . $logName;

        // 日志不存在
        if (! $redis->exists($logName)) {
            exit('RedisLog [' . $logName . '] is empty or doesnt exists.');
        }

        $logList = $redis->lrange($logName, 0, -1);

        $this->json($logList);
    }

    // 生成酒店（体验类）初始评分（后台值）
    public function createDefScoreAction()
    {
        $db = Com_DB::get(DB_PREFIX . 'core');

        $sql = "INSERT IGNORE INTO `v2_hotel_stats`(`hotel_id`, `def_score_1`, `def_score_2`, `def_score_3`, `def_score_4`)
            SELECT `id`, 4.5, 4.5, 4.5, 4.5
            FROM `v2_static_hotel_info`";

        $db->query($sql);

        exit('Done');
    }

    // 单元测试-所有会籍爬虫
    public function membershipAction()
    {
        $where = [
            'status' => 1,
            'has_card' => 1,
        ];

        if ($blocId = $this->getInt('id')) {
            $where += ['id' => $blocId];
        }

        $blocList = Dao('Core_V2StaticHotelBloc')->field('id, name')->where($where)->fetchAll();

        foreach ($blocList as $bloc) {

            // 实例化集团
            $membs = Model_Membership::factory($bloc['id']);

            // 模拟用错误账号登录
            try {
                $userData = $membs->loginWrong();
                $message2[] = [
                    'bloc_name' => $bloc['name'],
                    'user_data' => $userData,
                ];
            }
            catch (Exception $e) {
                $message2[] = [
                    'bloc_name'     => $bloc['name'],
                    'error_message' => $e->getMessage(),
                ];
            }

            // 模拟用测试账号登录
            try {
                $userData = $membs->loginQa();
                $message1[] = [
                    'bloc_name' => $bloc['name'],
                    'user_data' => $userData,
                ];
            }
            catch (Exception $e) {
                $message1[] = [
                    'bloc_name'     => $bloc['name'],
                    'error_message' => $e->getMessage(),
                ];
            }
        }

        echo '<h1>用错误账号登录:</h1>';
        pr($message2, 0);

        echo '<h1>用测试账号登录:</h1>';
        pr($message1, 0);

        exit();
    }
}