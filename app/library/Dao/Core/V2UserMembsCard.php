<?php

class Dao_Core_V2UserMembsCard extends Dao_Core_Abstract
{
    protected $_tableName = 'v2_user_membs_card';

    public function hasBlocCard($uid, $blocId)
    {
        return $this->where(['uid' => $uid, 'bloc_id' => $blocId])->fetchCount();
    }

    public function isRepeat($blocId, $cardNo)
    {
        return $this->where(['bloc_id' => $blocId, 'card_no' => $cardNo])->fetchCount();
    }
}