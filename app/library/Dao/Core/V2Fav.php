<?php

class Dao_Core_V2Fav extends Dao_Core_Abstract
{
    protected $_tableName = 'v2_fav';
    protected $_pk        = ['uid', 'target_type', 'target_id'];
}