<?php

/**
 * 支付订单模型
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Payment_Order extends Core_Model_Abstract
{
    // 订单状态
    const
        STATUS_UNPAID = 0,  // 未支付（缺省）
        STATUS_PAID   = 1;  // 已支付

    /**
     * 订单关联的产品
     *
     * @var Model_Payment_Product
     */
    public $product = null;

    public function __construct($orderSn)
    {
        if (! $orderSn) {
            throw new Model_Payment_Exception_Common('Invalid OrderSn');
        }

        if (! $this->_prop = Dao('Payment_Orders')->get($orderSn)) {
            throw new Model_Payment_Exception_Common('订单信息不存在。' . 'OrderSn:' . $orderSn);
        }
    }

    /**
     * 新增一个订单
     *
     * @param array $setArr
     * @return string $orderSn
     */
    public static function create(array $setArr)
    {
        if (! $setArr) {
            return false;
        }

        // 生成新的订单流水号
        $orderSn = self::createSn();
        $setArr['order_sn'] = $orderSn;

        // 下单时间
        if (! isset($setArr['created_at'])) {
            $setArr['created_at'] = $GLOBALS['_DATE'];
        }

        if (! Dao('Payment_Orders')->insert($setArr)) {
            return false;
        }

        return $orderSn;
    }

    /**
     * 生成订单流水号（16位数字）
     * 最大可以支持1分钟100万订单号不重复
     *
     * @return string $orderSn
     */
    public static function createSn()
    {
        $setArr = ['created_at' => $GLOBALS['_DATE']];
        $insertId = Dao('Payment_CreateSn')->insert($setArr);

        return date('ymdHi') . str_pad(substr($insertId, -6), 6, 0, STR_PAD_LEFT);
    }

    /**
     * 本订单是否已支付
     *
     * @return bool
     */
    public function isPaid()
    {
        return $this->_prop['status'] == self::STATUS_PAID ? true : false;
    }

    /**
     * 本订单是否已完成具体业务回调的时间
     *
     * @return bool
     */
    public function isCbSucceed()
    {
        return strtotime($this->_prop['cb_succeed_at']) > 0 ? true : false;
    }

    /**
     * 更新当前订单信息
     *
     * @param array $setArr
     * @return bool
     */
    public function update(array $setArr)
    {
        if (! $setArr) {
            return false;
        }

        // 断言 setArr 中的 value 不能为数组
        $this->assertValueNotArray($setArr);

        // 执行更新
        if ($result = Dao('Payment_Orders')->updateByPk($setArr, $this->_prop['order_sn'])) {
            // 更新 prop 数组
            $this->set($setArr);
        }

        return $result;
    }

    /**
     * 更新订单状态：已支付
     *
     * @param array $extraSetArr
     * @return bool
     */
    public function setPaid(array $extraSetArr = [])
    {
        $setArr = [
            'status'      => self::STATUS_PAID,
            'set_paid_at' => $GLOBALS['_DATE'],
        ];

        if ($extraSetArr) {
            $setArr = array_merge($setArr, $extraSetArr);
        }

        // 第三方平台的支付时间
        if (! isset($setArr['third_pay_time'])) {
            $setArr['third_pay_time'] = $GLOBALS['_DATE'];
        }

        // 执行更新
        return $this->update($setArr);
    }

    /**
     * 根据第三方订单号查找我方订单
     *
     * @param int $channelId
     * @param string $thirdOrderSn
     * @return string
     */
    public static function findByThirdOrderSn($channelId, $thirdOrderSn)
    {
        if ($channelId < 1 || ! $thirdOrderSn) {
            return false;
        }

        return Dao('Payment_Orders')->findByThirdOrderSn($channelId, $thirdOrderSn);
    }

    /**
     * 最终订单结算回调
     *
     * @return mixed
     */
    public function setFinished()
    {
        // 已回调过
        if ($this->isCbSucceed()) {
            return [-1, '重复回调'];
        }

        $product = Model_Payment_Product::factory($this->_prop['product_type']);

        try {
            $product->postFinishOrder($this);
        }
        catch (Exception $e) {

            // 记录回调失败日志
            Dao('Payment_CallbackErrLog')->insert([
                'order_sn'   => $this->_prop['order_sn'],
                'err_code'   => $e->getCode(),
                'err_msg'    => $e->getMessage(),
                'created_at' => $GLOBALS['_DATE'],
            ]);

            return [-2, '业务回调失败:' . $e->getMessage()];
        }

        // 完成具体业务回调
        $this->update(['cb_succeed_at' => $GLOBALS['_DATE']]);

        return [200, '业务回调成功'];
    }
}