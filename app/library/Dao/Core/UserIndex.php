<?php

class Dao_Core_UserIndex extends Dao_Core_Abstract
{
    protected $_tableName = 'user_index';
    protected $_pk        = 'uid';
    protected $_nameField = 'nickname';

    protected $_getPkByFields = ['nickname'];

    public function getDbSuffix($uid)
    {
        if (! $dbSuffix = $this->getField($uid, 'db_suffix')) {
            throws('用户 (UID: ' . $uid . ') 不存在或已被禁用，请联系管理员');
        }

        return $dbSuffix;
    }

    public function getUidByName($nickname)
    {
        return $this->_getPkByField('nickname', $nickname);
    }
}