<?php

class Model_Weixin_RedPack_Biz extends Core_Model_Abstract
{
    // 首次关注发送红包
    public static function firstSubscribe($openId)
    {
        // 暂时关闭
        return false;
    }
}