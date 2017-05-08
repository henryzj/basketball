<?php

class Dao_Ucenter_AccountIndex extends Dao_Ucenter_Abstract
{
    protected $_tableName     = 'account_index';
    protected $_getPkByFields = ['mobile'];

    public function getUidByMobile($mobile)
    {
        return $this->_getPkByField('mobile', $mobile);
    }

    public function getUserByMobile($mobile)
    {
        if (! $uid = $this->getUidByMobile($mobile)) {
            return array();
        }

        return $this->get($uid);
    }
}