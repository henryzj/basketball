<?php

/**
 * 黑名单封禁模块(全局)
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Blacklist
{
    const ACT_PUB_FEED = 1;  // 发布动态
    const ACT_COMMENT  = 2;  // 发表评论

    public static function isBlock($uid, $action)
    {
        // 1、先检测是否全行为封禁
        $info = Dao('Core_Blacklist')->get([$uid, 0]);

        if ($info && $info['expired_at'] > $GLOBALS['_DATE']) {
            return true;
        }

        // 2、再检测是否具体行为封禁
        $info = Dao('Core_Blacklist')->get([$uid, $action]);

        if ($info && $info['expired_at'] > $GLOBALS['_DATE']) {
            return true;
        }

        return false;
    }

    // 用户封禁
    public static function setBlock($uid, $action, $day)
    {
        if ($uid < 1) {
            throws('Invalid Uid');
        }

        $seconds = $day * 86400;
        $minutes = $day * 1440;

        if ($minutes > 43200) {
            throws('超出最大封禁时长');
        }

        $info = Dao('Core_Blacklist')->get([$uid, $action]);

        if ($info) {

            if ($info['expired_at'] > $GLOBALS['_DATE']) {
                throws('该用户正处于该行为封禁中');
            }

            Dao('Core_Blacklist')->updateByPk(['expired_at' => date("Y-m-d H:i:s", $GLOBALS['_TIME'] + $seconds)], [$uid, $action]);
        }

        else {

            // 本地封禁
            $setArr = [
                'uid'        => $uid,
                'action'     => $action,
                'created_at' => $GLOBALS['_DATE'],
                'expired_at' => date("Y-m-d H:i:s", $GLOBALS['_TIME'] + $seconds),
            ];

            if (! Dao('Core_Blacklist')->insert($setArr)) {
                throws('本地封禁失败');
            }
        }

        return true;
    }

    // 解禁
    public static function unBlock($uid, $action)
    {
        if ($uid < 1) {
            throws('Invalid Uid');
        }

        if (! self::isBlock($uid, $action)) {
            throws('该用户未被封禁');
        }

        // 本地解禁
        if (! Dao('Core_Blacklist')->updateByPk(['expired_at' => $GLOBALS['_DATE']], [$uid, $action])) {
            throws('本地解禁失败');
        }

        return true;
    }
}