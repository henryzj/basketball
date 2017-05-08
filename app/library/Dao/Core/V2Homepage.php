<?php

class Dao_Core_V2Homepage extends Dao_Core_AbstractStatic
{
    protected $_tableName = 'v2_homepage';

    protected function __CACHE__getList()
    {
        return $this->where(['status' => 1])->order('`display_order` DESC, `id` DESC')->fetchAll();
    }
}