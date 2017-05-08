<?php

class Dao_Core_V2SpecialAd extends Dao_Core_AbstractStatic
{
    protected $_tableName = 'v2_special_ad';

    protected function __CACHE__getAvList()
    {
        $where = [
            'start_time' => ['<=', $GLOBALS['_DATE']],
            'end_time'   => ['>=', $GLOBALS['_DATE']],
            'status'     => 1,
        ];

        return $this->where($where)->order('`id` DESC')->fetchAll();
    }
}