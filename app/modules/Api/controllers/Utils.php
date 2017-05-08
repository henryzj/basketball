<?php

/**
 * 完全开放的Api
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Controller_Utils extends Core_Controller_Api_Abstract
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

    // 生成图形验证码
    public function makeCaptchaAction()
    {
        $ns = $this->getx('ns') ?: 'default';

        $config = [
            'space'  => $ns,
            'width'  => 160,
            'height' => 60,
        ];

        // 直接输出二进制流
        (new Com_Captcha($config))->create();

        exit();
    }
}