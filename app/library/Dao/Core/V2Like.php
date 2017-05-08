<?php

class Dao_Core_V2Like extends Dao_Core_Abstract
{
    protected $_tableName = 'v2_like';
    protected $_pk        = ['target_type', 'target_id', 'uid'];
}