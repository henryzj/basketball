<?php

/**
 * 充值中心-模型工厂
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Payment.php 12005 2014-08-07 07:44:15Z jiangjian $
 */

class Model_Payment extends Core_Model_Abstract
{
    /**
     * 充值渠道定义
     * 格式：渠道Id => 对应PHP类名
     *
     * @var array
     */
    public static $channels = [
        10  => 'Alipay_Web',
        11  => 'Alipay_App',
        96  => 'Weixin_Wap',
        97  => 'Weixin_Scan',
        98  => 'Weixin_App',
        99  => 'Weixin_Js',
        500 => 'Remit',
    ];

    /**
     * 实例工厂
     *
     * @param int $channelId
     * @return Model_Payment_Channel_*
     */
    public static function factory($channelId)
    {
        if (! $className = self::getClassById($channelId)) {
            throw new Model_Payment_Exception_Common('Invalid ChannelId');
        }

        $className = 'Model_Payment_Channel_' . ucfirst($className);

        return new $className($channelId);
    }

    public static function getClassById($channelId)
    {
        return isset(self::$channels[$channelId]) ? self::$channels[$channelId] : null;
    }
}