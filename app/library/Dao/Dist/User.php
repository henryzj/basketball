<?php

class Dao_Dist_User extends Dao_Dist_Abstract
{
    protected $_tableName = 'user';
    protected $_pk        = 'uid';

    public function getDeadUids()
    {
        // 30天前
        $timeStamp = date('Y-m-d', time() - 3600*24*30);

        $where = array(
            'last_login_at' => array('<', $timeStamp),
        );

        return $this->where($where)->fetchPks();
    }
}