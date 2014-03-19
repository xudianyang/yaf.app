<?php

use Yaf\Controller_Abstract;
use Yaf\Dispatcher;
use Yar\YarClient;
class IndexController extends Controller_Abstract
{
    public function indexAction()
    {
        // 空的，加载一下模板
    }

    public function testYarApiAction()
    {
        Dispatcher::getInstance()->disableView();
        $client = new YarClient(
            array(
                'module' => 'index',
                'controller' => 'demoapi',
                'action' => 'getdata',
            ),
            array('args' => 'some parameters', 'format' => 'json')
        );
        $data = $client->api();
        print_r($data);
    }

    public function testLogAction()
    {
        // 空的，没有对应的模板，此处抛出的异常应被log/indexjob/index捕获并写入相应的储存介质
    }
}