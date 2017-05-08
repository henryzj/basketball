<?php

class Plugin_RouterUri extends Yaf_Plugin_Abstract
{
    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        if (! isset($_SERVER['HTTP_HOST']) || strpos($_SERVER['HTTP_HOST'], 'api.') === false) {
            return true;
        }

        $request->setModuleName('Api');
    }
}