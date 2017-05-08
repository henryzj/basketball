<?php

class Dao_Core_MobileVcode extends Dao_Core_Abstract
{
    protected $_tableName = 'mobile_vcode';
    protected $_pk        = ['mobile', 'scene'];
}