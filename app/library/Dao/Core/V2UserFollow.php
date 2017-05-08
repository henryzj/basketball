<?php

class Dao_Core_V2UserFollow extends Dao_Core_Abstract
{
    protected $_tableName = 'v2_user_follow';
    protected $_pk        = ['uid', 'follow_uid'];
}