<?php

class Dao_Ucenter_WxMpFollow extends Dao_Ucenter_Abstract
{
    protected $_tableName = 'wx_mp_follow';
    protected $_pk        = ['openid', 'app_id'];
}