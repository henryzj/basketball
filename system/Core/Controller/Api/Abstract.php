<?php

/**
 * Api 服务端控制器 抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Api.php 7057 2013-11-25 08:41:34Z jiangjian $
 */

abstract class Core_Controller_Api_Abstract extends Core_Controller_Abstract
{
    /**
     * 不加载视图（请勿修改）
     *
     * @var bool
     */
    public $yafAutoRender = false;

    /**
     * 统一响应输出
     *
     * @param string $message 响应文字
     * @param string $code 响应代码
     * @param mixed  $data 响应的数据
     * @return json
     */
    public function output($message, $code = 0, $data = null)
    {
        $resp = [
            'status_no'  => $code,
            'status_msg' => $message,
        ];

        $resp['time'] = $GLOBALS['_TIME'];

        // 为保持对客户端输出空hashMap和空list的结构一致
        // 这里统一把所有空数组转换为null
        if (is_array($data)) {
            $data = setEmptyArrayToNull($data);
        }

        // 总耗时
        $resp['elapsed'] = round(microtime(true) - $GLOBALS['_START_TIME'], 3);

        $resp['data'] = $data;

        $this->json($resp);
    }

    public function ok($data = null)
    {
        $this->output('OK', 0, $data);
    }
}