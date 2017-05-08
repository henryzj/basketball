<?php

class Dao_Core_V2StaticSearchIndex extends Dao_Core_AbstractStatic
{
    protected $_tableName = 'v2_static_search_index';

    protected function __CACHE__getList()
    {
        return $this->where(['status' => 1])->order('`display_order` DESC')->fetchAll();
    }
}