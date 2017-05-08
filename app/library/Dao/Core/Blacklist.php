<?php

class Dao_Core_Blacklist extends Dao_Core_Abstract
{
    protected $_tableName = 'blacklist';
    protected $_pk        = ['uid', 'action'];
}