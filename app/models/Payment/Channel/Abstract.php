<?php

/**
 * 支付模型-抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Payment_Channel_Abstract extends Core_Model_Abstract
{
    /**
     * 当前渠道标识
     *
     * @var int
     */
    protected $_channelId;

    /**
     * 下单跳转页模板
     *
     * @var string
     */
    protected $_bridgeTpl;

    /**
     * 是否处于沙盒测试模式
     *
     * @var bool
     */
    protected $_inSandBox = false;

    public function __construct($channelId)
    {
        $this->_channelId = $channelId;

        // 外网测试服永远为沙盒模式
        if (strpos($_SERVER['HTTP_HOST'], 'local.') !== false
         || strpos($_SERVER['HTTP_HOST'], 'test.') !== false
         || strpos($_SERVER['HTTP_HOST'], 'dev.') !== false
        ) {
            $this->_inSandBox = true;
        }

        $this->_loadConfig();
    }

    protected function _loadConfig()
    {

    }

    public function getBridgeTpl()
    {
        return $this->_bridgeTpl;
    }

    /**
     * 生成订单
     *
     * @param array $params
     * @return array
     */
    public function createOrder(array $params)
    {
        if ($params['uid'] < 1) {
            // TODO 保证不能随便传uid进来
            throw new Model_Payment_Exception_Common('下单人不存在');
        }

        // 检验产品类型
        $product = Model_Payment_Product::factory($params['product_type']);

        // 创建该类型的产品订单前的一些操作
        $product->preCreateOrder($params);

        // 执行新增订单
        $setArr = array(
            'channel_id'   => $this->_channelId,
            'status'       => Model_Payment_Order::STATUS_UNPAID,
            'created_at'   => $GLOBALS['_DATE'],
            'uid'          => $params['uid'],
            'total_fee'    => $params['total_fee'] ?: 0,    // 单位：元
            'product_type' => $params['product_type'],
            'product_info' => $params['product_info'],
        );

        if (! $orderSn = Model_Payment_Order::create($setArr)) {
            throw new Model_Payment_Exception_Common('保存订单失败');
        }

        $order = new Model_Payment_Order($orderSn);

        // 组装生成订单后的返回结果
        return $this->_buildCreateReturn($order, $product);
    }

    /**
     * 我方组装生成订单后的返回结果
     *     情况1：以JSON格式输出响应给客户端（缺省）
     *     情况2：以HTML表单格式自动提交GET/POST请求
     *     情况3：以URL信号的方式重新跳转
     *
     * @param Model_Payment_Order $order
     * @param Model_Payment_Product_Abstract $product
     * @return string
     */
    protected function _buildCreateReturn(Model_Payment_Order $order, Model_Payment_Product_Abstract $product)
    {
        return json_encode(array(
            'status' => 'success',
        ));
    }

    // 我方响应同步回跳
    public function respReturn(array $postData)
    {
        try {

            // 子类详细处理
            $result = $this->__respReturn($postData);

            $return = array(
                'status'  => 1,
                'message' => '支付成功，您购买的产品将在5分钟内到账或生效，请查收。',
            );

            // 我方订单流水号
            if (isset($result['order_sn'])) {
                $return['order_sn'] = $result['order_sn'];
            }

            // 订单总价格
            if (isset($result['total_fee'])) {
                $return['total_fee'] = $result['total_fee'];
            }
        }

        catch (Exception $e) {

            $return = array(
                'status'  => $e->getCode(),
                'message' => $e->getMessage(),
            );
        }

        // 统一的返回结果处理
        // 如果 Session 中有 $referUrl 则带着参数跳回去
        // 否则返回 $return 数组给 Controller 渲染模板页面
        return Model_Payment_Util::dealRespReturn($return);
    }

    protected function __respReturn(array $postData)
    {
        return array(
            'order_sn'  => '',
            'total_fee' => '',
        );
    }

    // 我方响应异步通知
    public function respNotify(array $postData)
    {
        return null;
    }

    // 输出通知错误和异常
    public function handleError(Exception $e)
    {
        return $e->getMessage();
    }

    // 记录响应通知数据
    public function markRespData($respData, $type = 0)
    {
        $setArr = array(
            'channel_id'  => $this->_channelId,
            'content'     => $respData ? http_build_query($respData) : '',
            'type'        => $type,
            'created_at'  => $GLOBALS['_DATE'],
        );

        // 读取原生RAW-POST数据
        if ($rawPostData = file_get_contents('php://input')) {
            $setArr['raw_post'] = $rawPostData;
        }

        return Dao('Payment_RespData')->insert($setArr);
    }

    /**
     * 获取订单实例
     *
     * @param string $orderSn 我方订单流水号
     * @return Model_Payment_Order $order
     */
    protected function _getOrderBySn($orderSn)
    {
        // 创建订单实例
        $order = new Model_Payment_Order($orderSn);

        // 检查订单状态
        if ($order->isPaid() || $order['third_order_sn']) {
            throw new Model_Payment_Exception_Repeat('订单已经支付，请勿重复请求');
        }

        return $order;
    }
}