<?php

/**
 * 我的统计 模型
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_User_Stats extends Model_User_Trait
{
    /**
     * 子类构函
     *
     * @return void
     */
    protected function _initTrait()
    {
        $this->_prop = $this->DaoDs('UserStats')->touch($this->_uid);
    }

    /**
     * 指定字段更新
     *
     * @return bool
     */
    public function update(array $setArr)
    {
        if (! $setArr) {
            return false;
        }

        // 断言 setArr 中的 value 不能为数组
        $this->assertValueNotArray($setArr);

        // 执行更新
        if ($result = $this->DaoDs('UserStats')->updateByPk($setArr, $this->_uid)) {
            // 更新 prop 数组
            $this->set($setArr);
        }

        return $result;
    }

    /**
     * 指定字段归零
     *
     * @return bool
     */
    public function reset($field)
    {
        $this->_prop[$field] = 0;

        return $this->DaoDs('UserStats')->updateByPk([$field => 0], $this->_uid);
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

        return $this->DaoDs('UserStats')->incrByPk($this->_uid, $field, $step);
    }

    /**
     * 指定字段自减
     *
     * @return bool
     */
    public function decr($field, $step = 1)
    {
        return $this->incr($field, -$step);
    }
}