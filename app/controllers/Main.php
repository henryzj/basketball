<?php

/**
 * 主界面
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Main.php 11509 2014-06-23 08:31:10Z jiangjian $
 */

class Controller_Main extends Controller_Abstract
{
    protected $_checkAuth = 0;

    /**
     * 主界面
     */
    public function indexAction()
    {
        exit('敬请期待 Wp.StayLife.Cn');
    }

    // 生成图形验证码
    public function makeCaptchaAction()
    {
        $ns = $this->getx('ns') ?: 'default';

        $config = [
            'space'  => $ns,
            'width'  => 100,
            'height' => 40,
            'length' => 4,
        ];

        // 直接输出二进制流
        (new Com_Captcha($config))->create();

        exit();
    }

    // 关于我们
    public function aboutUsAction()
    {
        // 直接渲染模板
    }

    // 用户协议
    public function protocolAction()
    {
        // 直接渲染模板
    }
}