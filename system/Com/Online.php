<?php

/**
 * 在线用户名单 通用模型
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_Online extends Core_Model_Abstract
{
    const LIST_KEY = 'UserOnlineList';

    // 更新间隔（单位秒）
    const UPDATE_INTERVAL = 300;

    private static $_redis;

    private static function _redis()
    {
        if (! self::$_redis) {
            self::$_redis = F('Redis')->default;
        }

        return self::$_redis;
    }

    // 更新在线信息
    public static function update($uid, $callback = null)
    {
        // 上次访问时间
        $lastVisitTime = self::getLastVisitedTime($uid);

        // 小于更新间隔（300秒）则不更新
        if ($GLOBALS['_TIME'] - $lastVisitTime < self::UPDATE_INTERVAL) {
            return -1;
        }

        self::_redis()->zAdd(self::LIST_KEY, $GLOBALS['_TIME'], $uid);

        // 累计该用户在线时长（精确到分钟）
        $incrMins = floor(self::UPDATE_INTERVAL / 60);

        if (is_callable($callback)) {
            $callback($incrMins);
        }

        return true;
    }

    public static function getCount()
    {
        return self::_redis()->zCard(self::LIST_KEY);
    }

    // 当前在线列表
    public static function getList($start = 0, $pageSize = 10)
    {
        // 倒序取有序集合
        $stop = $pageSize + $start - 1;

        if (! $list = self::_redis()->zRevRange(self::LIST_KEY, $start, $stop, 'WITHSCORES')) {
            return [];
        }

        return $list;
    }

    // 清理N小时前的在线用户
    public static function clear($hoursBefore = 8)
    {
        $timeStamp = $GLOBALS['_TIME'] - $hoursBefore * 3600;

        return self::_redis()->zRemRangeByScore(self::LIST_KEY, '-inf', '(' . $timeStamp);
    }

    // 上次访问时间
    public static function getLastVisitedTime($uid)
    {
        return self::_redis()->zScore(self::LIST_KEY, $uid);
    }

    // 指定用户是否在线
    public static function isOnline($uid)
    {
        // 上次访问时间
        $lastVisitTime = self::getLastVisitedTime($uid);

        // 小于300秒则算作在线
        if ($GLOBALS['_TIME'] - $lastVisitTime < self::UPDATE_INTERVAL) {
            return 1;
        }

        return 0;
    }
}