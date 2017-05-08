<?php

class Dao_Core_V2FeedIndex extends Dao_Core_Abstract
{
    protected $_tableName = 'v2_feed_index';
    protected $_pk        = ['target_type', 'target_id'];

    protected $_getPkByFields = ['id'];

    public function getInfoByFeedId($feedId)
    {
        if (! $pk = $this->_getPkByField('id', $feedId)) {
            return [];
        }

        return $this->get($pk);
    }
}