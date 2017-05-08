<?php

/**
 * 错误异常处理器
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Error.php 9716 2014-03-11 12:12:41Z jiangjian $
 */

class Core_Controller_Api_Error extends Core_Controller_Api_Abstract
{
    public function errorAction()
    {
        $e = $this->_request->getException();

        if (! $e instanceof Exception) {
            $this->output('Unknow Error/Warning', -999);
        }

        $this->output($e->getMessage(), ($e->getCode() ?: -1));

        return false;
    }
}