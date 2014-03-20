#Yaf.app

一个基于Yaf MVC的PHP应用程序框架。

学习与使用Yaf的PHPer有许多，每个人的“姿势”都不尽相同，这是一种好现象，因为说明都动脑了。本项目没有多少创新，很多程序设计的思想与框架支持的功能都来源于其他公开或非公开项目。

##需求

####软件版本

* PHP-5.4+
* Redis-2.6.17

####PHP扩展

* Yaf-2.2.9+
* XHProf-0.9.4
* phpredis-2.2.4
* Yar-1.2.1

##特性

* 消息队列
* 远程调用（RPC）
* 支持XHProf性能分析
* 模块自定义配置及配置可回调
* 模块之间松耦合
* 格式化输出（json/Msgpack/serialize/plain/view）
* 异常捕获

##使用说明

###消息队列

由于PHP+Redis实现的消息队列，能相当易于部署与维护，对于小型应用来说此种模式的消息队列足矣解决问题。故这里将[`php-resque`](https://github.com/chrisboulton/php-resque)与Yaf进行了整合，并修改部分代码。最终的目的是能够使其与Yaf的命令行模式结合，完成后台执行PHP脚本。关于php-resque的更多介绍请参考：[用PHP实现守护进程任务后台运行与多线程](http://avnpc.com/pages/run-background-task-by-php-resque)

#####1.Worker启动脚本

修改./application/library/Resque/bin/Yaf-cli

!/usr/bin/env /Users/xudianyang/Server/php-5.4/bin/php

修改为php解释器的对应路径

define('ROOT_PATH',     '/Users/xudianyang/PhpstormProjects/yaf.app-src');

ROOT_PATH常量修改为应用的主目录

define('ASSETS_URL',    'http://assets.phpboy.net/');

ASSETS_URL常量暂用于程序的资源文件目录，如：js,css等

define('BACKEND_URL',   'http://backend.phpboy.net/');

BACKEND_URL常量表示应用域名，应指向public

```php

#!/usr/bin/env /Users/xudianyang/Server/php-5.4/bin/php

<?php
define('DS',            '/');
define('APP_NAME',      'application');
define('ROOT_PATH',     '/Users/xudianyang/PhpstormProjects/yaf.app-src');
define('INI_PATH',      ROOT_PATH.DS.'conf'.DS.'app.ini');

define('ASSETS_URL',    'http://assets.phpboy.net/');
define('BACKEND_URL',   'http://backend.phpboy.net/');

use Yaf\Loader as InternalLoader;
use Resque\Resque;
use Resque\Resque\Worker;
use Resque\Resque\Log;
use Resque\Resque\Redis;
use Psr\Log\LogLevel;

$loader = InternalLoader::getInstance(ROOT_PATH . DS . APP_NAME . DS . 'library');
spl_autoload_register(array($loader, 'autoload'));

//......
```

#######启动worker

`php Yaf-cli start`

支持的参数

* `--host=Redis主机（默认为127.0.0.1）` 

* `--port=Redis端口（默认为6379）` 

* `--prefix=Redis Key前缀`

* `--database=持久化数据库编号`

* `--queue=队列名称（默认*,监听所有的工作任务）`

* `--process=进程数（默认1，可以开启多个减少延迟）`

* `--blocking=是否阻塞(0、1)`

* `--interval=轮循间隔（默认1秒）`

* `--pid-path=PHP进程ID文件路径（默认/tmp/resque）`
	
#######关闭worker

`php Yaf-cli stop`

支持的参数

* `--queue=队列名称（默认*,监听所有的工作任务）`

#######启动示例

```bash

➜ application/library/Resque/bin>php Yaf-cli start --process=5 --queue=log
[notice] Starting Worker xudianyang-mac.lan:2447:log
[notice] Starting Worker xudianyang-mac.lan:2446:log
[notice] Starting Worker xudianyang-mac.lan:2448:log
[notice] Starting Worker xudianyang-mac.lan:2450:log
[notice] Starting Worker xudianyang-mac.lan:2449:log

```

#####2.使用消息队列记录程序异常日志

######1.初始化异常表结构

```sql
CREATE TABLE IF NOT EXISTS `app_error_log` (
  `logid` int(10) NOT NULL AUTO_INCREMENT,
  `host` char(50) DEFAULT NULL,
  `uri` char(255) DEFAULT NULL,
  `query` char(255) DEFAULT NULL,
  `module` char(50) DEFAULT NULL,
  `controller` char(255) DEFAULT NULL,
  `action` char(50) DEFAULT NULL,
  `params` text,
  `exception` char(255) DEFAULT NULL,
  `code` int(4) DEFAULT NULL,
  `message` varchar(1000) DEFAULT NULL,
  `file` varchar(500) DEFAULT NULL,
  `line` int(4) DEFAULT NULL,
  `timestamp` int(10) DEFAULT NULL,
  `datetime` char(25) DEFAULT NULL,
  PRIMARY KEY (`logid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1

```

######2.使用异常日志——创建日志工作任务

在上述开启了worker之后，我们就可以入队列与出队列，完成工作任务了。本人在使用php-resque与Yaf时，将Yaf命令行路由分发与php-resque的工作任务(job)结合起来使用，即写消息队列的工作任务代码就和写其他正常的Action一样，只不过controller继承内置的ServiceJob类。

示例代码：./applicaiton/modules/Log/controllers/Indexjob.php

```php

<?php

use Core\ServiceJob;
use Log\LogModel;

class IndexJobController extends ServiceJob
{
    public function init()
    {
        parent::init();
    }
    public function indexAction()
    {
        $data = $this->getRequest()->getParams();
        $log = new LogModel();
        $log->add($data);
    }
}

```

######3.使用异常日志——消息入队列

示例代码：./public/index.php

```php

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


```

可以看到

```php

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


```

所有异常都会被捕获，在开启日志队列时，会通过ErrorLog类的errorLog方法进行入队列操作。

```php
<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Core;

use Exception as Exception;
use Yaf\Request_Abstract;
use Yaf\Application;
use Resque\Resque;

class ErrorLog
{
// ......
    public function errorLog()
    {
        if (isset(Application::app()->getConfig()->application->queue)
            && isset(Application::app()->getConfig()->application->queue->redis)
            && isset(Application::app()->getConfig()->application->queue->log)
        ) {
            $redis_config = Application::app()->getConfig()->application->queue->redis->toArray();
            $server   = $redis_config['host'] . ':'. $redis_config['port'];
            $database = isset($redis_config['database']) ? $redis_config['database'] : null;
            Resque::setBackend($server, $database);
            $args = array(
                'module'     => Application::app()->getConfig()->application->queue->log->module,
                'controller' => Application::app()->getConfig()->application->queue->log->controller,
                'action'     => Application::app()->getConfig()->application->queue->log->action,
                'args'       => $this->toArray(),
            );
            $queue_name = Application::app()->getConfig()->application->queue->log->name;
            Resque::enqueue($queue_name, 'Resque\Job\YafCLIRequest', $args, true);
        }
    }
}

```

重点在

```php
Resque::setBackend($server, $database);
Resque::enqueue($queue_name, 'Resque\Job\YafCLIRequest', $args, true);

```

######4.使用异常日志——执行工作任务

./application/library/Resque/Job/YafCLIRequest.php

```php

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

```

worker进程在检测队列时，如果队列不为空，就会依次进行出队列的操作，运行YafCLIRequest::perform()方法，通过Yaf命令行下的路由分发，完成工作任务。

















