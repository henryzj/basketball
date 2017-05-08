<?php

/**
 * 我的初次使用引导提示 模型
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_User_Guide extends Model_User_Trait
{
    public static $actions = array(
        'guide_asst_ignore_tips'   => 1,  // 是否不再提示 License 弹框
    );

    // 是否显示提示
    public function isNeedRemind($action)
    {
        $pk = ['uid' => $this->_uid, 'action' => self::$actions[$action]];

        $row = $this->DaoDs('UserGuide')->touch($pk);

        if ($row['next_remind_time'] == 0) {
            return true;
        }

        // 已设为永不提醒
        if ($row['next_remind_time'] == 1) {
            return false;
        }

        if ($GLOBALS['_TIME'] < $row['next_remind_time']) {
            return false;
        }

        return true;
    }

    // 设为已读（永不提醒）
    public function ignore($action)
    {
        $pk = ['uid' => $this->_uid, 'action' => self::$actions[$action]];

        return $this->DaoDs('UserGuide')->updateByPk(['next_remind_time' => 1], $pk);
    }

    // 下次再说（1小时之后再提示）
    public function remindNext($action)
    {
        $pk = ['uid' => $this->_uid, 'action' => self::$actions[$action]];

        return $this->DaoDs('UserGuide')->updateByPk(['next_remind_time' => $GLOBALS['_TIME'] + 3600], $pk);
    }
}