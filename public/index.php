<?php
/**
 * Yaf.app Framework
 *
 * 程序入口（普通请求、API请求）
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

define('DS',            '/');
define('ES',            'EXCEPTION-STDERR');
define('APP_NAME',      'application');
define('ROOT_PATH',      realpath(dirname(__FILE__).'/../'));
define('INI_PATH',       ROOT_PATH.DS.'conf'.DS.'app.ini');

define('ASSETS_URL',    'http://assets.phpboy.net/');
define('BACKEND_URL',   'http://backend.phpboy.net/');

use Yaf\Application;
use Yaf\Dispatcher;
use Yaf\Request\Simple as RequestSimple;
use Core\ErrorLog;
use Exception as Exception;
use Resque\Resque;
use Sender\Http as SenderHttp;

if (isset($_SERVER['HTTP_USER_AGENT']) && substr($_SERVER['HTTP_USER_AGENT'], 0, 11) === 'PHP Yar Rpc') {
    /*
     * Yar_Server导出的API类
     *
     * 当请求通过Yar_Client进行远程调用时生效
     *
     * @package Global
     */
    class Service
    {
        /**
         * 导出API的api方法
         *
         * @access public
         * @param string $module 应用模块名
         * @param string $controller 对应模块内的控制器
         * @param string $action 对应控制器中的动作名
         * @param mixed $parameters 请求传递的参数
         * @return string API调用的响应正文
         */
        public function api($module, $controller, $action, $parameters)
        {
            try {
                $app = new Application(INI_PATH, 'product');
                $request = new RequestSimple('API', $module, $controller, $action, $parameters);
                $response = $app->bootstrap()->getDispatcher()->dispatch($request);
                return $response->getBody();
            } catch(Exception $e) {
                if (Application::app()->getConfig()->application->queue->log->switch) {
                    $error = new ErrorLog($e, Dispatcher::getInstance()->getRequest());
                    $error->errorLog();
                }
                $error = explode(ES, $e->getMessage(), 2);
                if (isset($error[1])) {
                    return $error[1];
                }
            }
        }
    }

    $server = new Yar_Server(new Service());
    $server->handle();
} else {
    try {
        $app = new Application(INI_PATH, 'product');
        $app->bootstrap()->run();
    } catch(Exception $e) {
        $sender = new SenderHttp();
        if (Application::app()->getConfig()->application->debug) {
            $sender->setStatus(503, 'Exception: '.$e->getMessage());
        } else {
            $sender->setStatus(503, 'Exception');
        }
        $sender->send();
        if (Application::app()->getConfig()->application->queue->log->switch) {
            $error = new ErrorLog($e, Dispatcher::getInstance()->getRequest());
            $error->errorLog();
        } else {
            echo $e->getMessage();
        }
    }
}