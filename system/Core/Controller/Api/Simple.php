<?php

/**
 * Api 服务端控制器 简化版
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Api.php 7057 2013-11-25 08:41:34Z jiangjian $
 */

abstract class Core_Controller_Api_Simple extends Core_Controller_Api_Abstract
{
    /**
     * 对称验证私钥（可选）
     * 如果设置了私钥，则表示本控制器内所有方法都需要验证签名
     *
     * @var string
     */
    protected $_secretKey;

    public function init()
    {
        parent::init();

        // 如果设置了私钥，则需要验证签名
        if ($this->_secretKey) {
            $this->_verifySign();
        }
    }

    // 验证请求签名（防止篡改请求参数）
    protected function _verifySign()
    {
        $postData = $this->getPost();

        if (! Helper_Api::verifySign($postData, $this->_secretKey)) {
            $this->output('签名验证失败', -9999);
        }
    }
}