<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Resque\Job;

use Yaf\Application;
use Yaf\Dispatcher;
use Yaf\Request\Simple as RequestSimple;
use Core\ErrorLog;
use Resque\Resque;
use Exception as Exception;

class YafCLIRequest
{
    public function perform()
    {
        try {
            $app = new Application(INI_PATH);
            $request = new RequestSimple('CLI', $this->args['module'], $this->args['controller'], $this->args['action'], $this->args['args']);
            $app->bootstrap()->getDispatcher()->dispatch($request);
        } catch(Exception $e) {
            if (Application::app()->getConfig()->application->queue->log->switch) {
                $error = new ErrorLog($e, Dispatcher::getInstance()->getRequest());
                $error->errorLog();
            }
        }
    }
}