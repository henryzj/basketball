<?php

class Dao_Dist_UserHourlyStats extends Dao_Dist_UserDailyAbstract
{
    protected $_tableName = 'user_hourly_stats';
    protected $_pk        = ['uid', 'today', 'hour'];
}