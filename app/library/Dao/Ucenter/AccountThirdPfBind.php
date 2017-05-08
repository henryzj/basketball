<?php

class Dao_Ucenter_AccountThirdPfBind extends Dao_Ucenter_Abstract
{
    protected $_tableName = 'account_thirdpf_bind';
    protected $_pk        = ['platform', 'third_uid'];

    public function getThirdUid($uid, $platform)
    {
        $where = [
            'uid'      => $uid,
            'platform' => $platform,
        ];

        return $this->field('third_uid')->where($where)->fetchOne();
    }
}