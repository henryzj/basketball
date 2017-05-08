<?php

class Dao_Core_V2UserFans extends Dao_Core_Abstract
{
    protected $_tableName = 'v2_user_fans';
    protected $_pk        = ['uid', 'fans_uid'];
}