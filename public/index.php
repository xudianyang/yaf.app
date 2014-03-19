<?php
/**
 * Yaf.app Framework
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

if (substr($_SERVER['HTTP_USER_AGENT'], 0, 11) === 'PHP Yar Rpc') {
    class Service
    {
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

