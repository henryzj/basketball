<?php

abstract class Dao_Dist_Abstract extends Dao_Abstract
{
    /**
     * 分库前缀
     *
     * @var int
     */
    protected static $_dbPrefix = 'dist_';

    /**
     * 分库后缀（存数组中防止重复查）
     *
     * @var array
     */
    protected static $_dbSuffix = array();

    public function loadDs($uid)
    {
        if (! isset(self::$_dbSuffix[$uid])) {
            self::$_dbSuffix[$uid] = Dao('Core_UserIndex')->getDbSuffix($uid);
        }

        return $this->setDs(self::$_dbSuffix[$uid]);
    }

    public function setDs($dbSuffix)
    {
        $this->_dbName = static::$_dbPrefix . $dbSuffix;

        return $this;
    }
}