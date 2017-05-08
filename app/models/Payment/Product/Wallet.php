<?php

// 给钱包充值
class Model_Payment_Product_Wallet extends Model_Payment_Product_Abstract
{
    protected $_title = '给钱包充值';

    public function preCreateOrder(array &$params)
    {
        $params['product_info'] = 0;

        // 微信限制了单笔最多充值10w元
        $params['total_fee'] = min($params['total_fee'], 100000);
    }

    public function postFinishOrder(Model_Payment_Order $order)
    {
        $uid = $order['uid'];

        $user = new Model_User($uid);
        $user->wallet->deposit($order['total_fee'] * 100, 50099, '给钱包充值', 'rmb');

        return true;
    }
}