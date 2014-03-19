<?php

use Core\ServiceApi;

class DemoApiController extends ServiceApi
{
    public function init()
    {
        parent::init();
    }

    public function getDataAction()
    {
        $this->sendOutput("这是通过远程调用返回的数据(Yar)传递的参数: args => " . $this->getRequest()->getParam('args'));
    }
}