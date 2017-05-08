<?php

class Dao_Core_AccessLogConfig extends Dao_Core_Abstract
{
    protected $_tableName = 'access_log_config';
    protected $_pk        = 'request_uri';
}