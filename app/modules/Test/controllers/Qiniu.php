<?php

class Controller_Qiniu extends Core_Controller_Test
{
    public $yafAutoRender = false;

    public function indexAction()
    {
        $stream = file_get_contents("H:\www\morecruit\mp4\beta\web\img\mbox_icon\happy.png");
        $result = Com_Qiniu::uploadByStream($stream);

        vd($result);
    }
}