<?php

// 预订酒店-支付服务费
class Model_Payment_Product_BookHotel extends Model_Payment_Product_Abstract
{
    protected $_title = '预订酒店-支付服务费';

    public function preCreateOrder(array &$params)
    {
        $bookHotelSn = $params['product_info'];
        $bookHotelOrder = new Model_Hotel_Order($bookHotelSn);

        if ($params['uid'] != $bookHotelOrder['uid']) {
            throws('这不是你的酒店订单');
        }

        if ($bookHotelOrder['status'] != Model_Hotel_Order::STATUS_UNPAID) {
            throws('订单不处于“待支付”状态');
        }

        // 入住日期
        // 当天18点以前支付可以订明天的酒店，18点以后只能订后天的
        $minCheckinDate = Model_Hotel::getMinCheckinDate();

        if ($bookHotelOrder['checkin_date'] < $minCheckinDate) {
            throws('对不起，订单已超时，订单必须在入住日期前一天' . Model_Hotel::BOOK_TOMORROW_BEFORE_CLOCK . '点之前支付');
        }

        // 以订单中的数据为准
        // 待支付的服务费（元）
        $params['total_fee'] = $bookHotelOrder['serv_fee'];

        // 传给外部用
        $params['hotel_order'] = $bookHotelOrder;

        return true;
    }

    public function postFinishOrder(Model_Payment_Order $order)
    {
        $bookUid        = $order['uid'];
        $bookHotelSn    = $order['product_info'];
        $bookHotelOrder = new Model_Hotel_Order($bookHotelSn);

        // 更新酒店订单状态
        $bookHotelOrder->setPaid($order['order_sn']);

        // 记录我的收支明细
        Dao('Massive_LogUserMoney')->insert([
            'uid'        => $bookUid,
            'type'       => Model_User_Wallet::TYPE_OUT,
            'action'     => 50801,
            'money'      => $bookHotelOrder['serv_fee'] * 100,  // 单位：分
            'memos'      => '预订酒店-支付服务费，酒店订单：' . $bookHotelSn,
            'created_at' => $GLOBALS['_DATE'],
        ]);
    }

    public function walletBalancePay(Model_User $user, array &$params)
    {
        if ($user['money'] < $params['total_fee'] * 100) {
            throws('余额不足，请选择其他支付方式');
        }

        $user->wallet->consume($params['total_fee'] * 100, 50801, '预订酒店-支付服务费（账户余额），酒店订单：' . $params['product_info']);

        $params['hotel_order']->setPaid('账户余额支付');
    }
}