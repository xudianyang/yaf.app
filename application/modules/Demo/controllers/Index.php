<?php
/**
 * Yaf.app Framework
 *
 * Index.php
 *
 * Index控制器
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 * @package Demo
 */

use Yaf\Controller_Abstract;
use Yaf\Dispatcher;
use Yar\YarClient;
use Yaf\Registry;

/**
 * Class IndexController
 * @package Demo
 */
final class IndexController extends Controller_Abstract
{
    /**
     * 默认动作
     * @access public
     */
    public function indexAction()
    {
        // 空的，加载一下模板
    }

    /**
     * 演示yar远程调用
     * @access public
     */
    public function testYarApiAction()
    {
        Dispatcher::getInstance()->disableView();
        $client = new YarClient(
            array(
                'module' => 'demo',
                'controller' => 'demoapi',
                'action' => 'getdata',
            ),
            array('args' => 'some parameters', 'format' => 'json')
        );
        $data = $client->api();
        print_r($data);
    }

    /**
     * 演示日志队列
     * @access public
     */
    public function testLogAction()
    {
        // 空的，没有对应的模板，此处抛出的异常应被log/indexjob/index捕获并写入相应的储存介质
    }

    /**
     * 测试redis pub/sub
     */
    public function subAction()
    {
        Dispatcher::getInstance()->disableView();
        $redis = Registry::get('mount')->get('redis')->getResource();
        $redis->subscribe(array('log'), array($this, 'echoData'));
    }

    public function echoData($redis, $channel, $msg)
    {
        header("Content-Type: text/html;charset=utf8");
        var_dump($msg);
        var_dump($channel);
        var_dump($redis);
        exit;
    }
}