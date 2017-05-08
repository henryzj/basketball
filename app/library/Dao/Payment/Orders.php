<?php

class Dao_Payment_Orders extends Dao_Payment_Abstract
{
    protected $_tableName = 'orders';
    protected $_pk        = 'order_sn';

    // 因为本表操作频繁，不做缓存
    protected $_isDaoNeedCache = false;

    /**
     * 根据第三方订单号查找我方订单号
     *
     * @param int $channelId
     * @param string $thirdOrderSn
     * @return string $orderSn
     */
    public function findByThirdOrderSn($channelId, $thirdOrderSn)
    {
        $where = array(
            'channel_id'     => $channelId,
            'third_order_sn' => $thirdOrderSn,
        );

        return $this->where($where)->fetchPk();
    }
}