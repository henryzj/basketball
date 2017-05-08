<?php


/**
 * 我的每小时行为次数统计
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_User_HourlyStats extends Model_User_Trait
{
    /**
     * 子类构函
     *
     * @return void
     */
    protected function _initTrait()
    {
        $this->_prop = $this->DaoDs('UserHourlyStats')->touch([$this->_uid, TODAY, date('G')]);
    }

    /**
     * 指定字段自增
     *
     * @return bool
     */
    public function incr($field, $step = 1)
    {
        if (! isset($this->_prop[$field])) {
            $this->_prop[$field] = 0;
        }

        $this->_prop[$field] += $step;

        return $this->DaoDs('UserHourlyStats')->incrByPk([$this->_uid, TODAY, date('G')], $field, $step);
    }
}