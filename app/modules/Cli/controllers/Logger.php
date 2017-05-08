<?php

/**
 * 日志处理
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Controller_Logger extends Core_Controller_Cli
{
    // 落地归档
    // 将日志从 Redis 转存到 DB 中
    public function redisLogToDbAction()
    {
        $result = Com_Logger_Redis::exportAllToDb();

        // 归档结果
        if ($result) {
            Com_Logger_File::info('redisLogToDb', $result);
        }
        else {
            exit('Empty RedisLog');
        }

        pr($result);
    }
}