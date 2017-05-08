<?php

class Dao_Dist_UserDailyAbstract extends Dao_Dist_Abstract
{
    // 每日数据因为经常清理
    // 所以单条缓存时间不需要太长
    protected $_cacheTTL = 86400;

    // 删除指定日期之前的玩家每日数据
    public function clearUntillDate($date)
    {
        return $this->where(array('today' => array('<=', $date)))->delete();
    }
}