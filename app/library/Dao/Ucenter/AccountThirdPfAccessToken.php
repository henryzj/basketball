<?php

class Dao_Ucenter_AccountThirdPfAccessToken extends Dao_Ucenter_Abstract
{
    protected $_tableName = 'account_thirdpf_access_token';
    protected $_pk        = ['uid', 'source', 'app_id'];
}