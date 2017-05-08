<?php

require_once __DIR__ . '/Abstract.php';

class Controller_Cloud extends Controller_Abstract
{
    // 本方法只为兼容 Stay1.3 以下（1.3以后本方法移入User控制器）
    public function getQiniuUpTokenAction()
    {
        $upToken = Com_Qiniu::getUploadToken();

        $this->output('上传凭证', 0, $upToken);
    }
}