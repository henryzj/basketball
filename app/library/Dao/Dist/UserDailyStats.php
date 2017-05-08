<?php

class Dao_Dist_UserDailyStats extends Dao_Dist_UserDailyAbstract
{
    protected $_tableName = 'user_daily_stats';
    protected $_pk        = ['uid', 'today'];
}