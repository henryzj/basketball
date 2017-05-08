<?php

/**
 * 完全开放的JsApi
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Controller_Js extends Core_Controller_Api_Simple
{
    // 完全公开，无需验签
    protected $_secretKey = null;

    public function getSignPackageAction()
    {
        $url = $this->get('url');
        $callback = $this->get('callback');

        $package = Model_Weixin_Core::getJsSignPackage($url);

        if ($callback) {
            exit($callback . '(' . json_encode($package) . ')');
        }
        else {
            exit(json_encode($package));
        }
    }

    public function makeQrCodeAction()
    {
        $string = $this->get('string');

        exit(Com_QrCode::make($string));
    }
}