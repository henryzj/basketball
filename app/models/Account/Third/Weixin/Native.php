<?php

/**
 * 第三方通行证
 * 原生APP通过微信SDK登陆
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Account_Third_Weixin_Native extends Model_Account_Third_Weixin_Abstract
{
    protected $_appId     = WX_NATIVE_APP_ID;
    protected $_appSecret = WX_NATIVE_APP_SECRET;
}