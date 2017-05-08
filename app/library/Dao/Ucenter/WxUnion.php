<?php

class Dao_Ucenter_WxUnion extends Dao_Ucenter_Abstract
{
    protected $_tableName = 'wx_union';
    protected $_pk        = ['unionid', 'source', 'app_id'];
}