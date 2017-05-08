<?php

class Dao_Massive_LogAbstract extends Dao_Massive_Abstract
{
    // 因为本表为日志记录型，不做缓存
    protected $_isDaoNeedCache = false;
}