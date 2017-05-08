<?php

class Dao_Core_V2Topic extends Dao_Core_Abstract
{
    protected $_tableName = 'v2_topic';
    protected $_nameField = 'keyword';

    protected $_getPkByFields = ['keyword'];

    public function getIdByKeyword($keyword)
    {
        return $this->_getPkByField('keyword', $keyword);
    }
}