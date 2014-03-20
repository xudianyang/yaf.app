#Yaf.app

一个基于Yaf MVC的PHP应用程序框架，在工作中使用Yaf加了一些特定程序元素，需要的自取，此项目并非使用Yaf的标准，是个人使用Yaf的总结，用得不对的地方请大神勿喷，并告知小弟，不胜感激。

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
* msgpack-0.5.5
* pcntl

 
####INI配置

`yaf.ini`

```
extension=yaf.so
yaf.cache_config=1
yaf.use_namespace=1
```

`xhprof.ini`

```
extension=xhprof.so
xhprof.output_dir=/Users/xudianyang/Server/var/run/xhprof
```

`msgpack.ini`

```
extension=msgpack.so
```

`redis.ini`

```
extension=redis.so
```

`yar`

```
extension=yar.so
```


##特性

* 消息队列
* 远程调用（RPC）
* 支持XHProf性能分析
* 模块自定义配置及配置可回调
* 模块之间松耦合
* 格式化输出（json/Msgpack/serialize/plain/view）
* 异常捕获

##使用说明

###配置文件机制

####全局配置app.ini

yaf application的ini配置说明请参考[yaf手册](http://cn2.php.net/manual/zh/yaf.appconfig.php)或者相关文档，下面是本人的配置：

```
[common]
application.debug=1
application.directory=ROOT_PATH "/" APP_NAME "/"
application.bootstrap=ROOT_PATH "/" APP_NAME "/" Bootstrap.php
application.dispatcher.defaultModule="index"
application.dispatcher.defaultController="index"
application.dispatcher.defaultAction="index"
application.dispatcher.throwException=1
application.modules="Index,Log"
application.view.ext="phtml"

[product:common]
;memcached
application.memcached.0.host=127.0.0.1
application.memcached.0.port=11211
application.memcached.0.weight=50
application.memcached.1.host=127.0.0.1
application.memcached.1.port=11212
application.memcached.1.weight=50
;database
application.database.driver="Pdo_mysql"
application.database.host="127.0.0.1"
application.database.port=3306
application.database.username="root"
application.database.password="123123"
application.database.dbname="test"
application.database.charset="utf8"
;log queue
application.queue.log.switch=1
application.queue.log.tablename=app_error_log
application.queue.log.name=log
application.queue.log.module=Log
application.queue.log.controller=Indexjob
application.queue.log.action=Index
;queue
application.queue.redis.host=127.0.0.1
application.queue.redis.port=6379
;xhprof
application.xhprof.open=1
application.xhprof.namespace=yaf-app
;XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY
application.xhprof.flags= XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY
application.xhprof.ignored_functions.0=call_user_func
application.xhprof.ignored_functions.1=call_user_func_array
```
其中：

* memcached段为memcached的连接信息
* database段为默认数据库连接信息
* queue为消息队列配置，queue.log为日志队列的配置，包括日志数据据表名，日志的工作任务Action
* queue.redis为消息队列储存的redis连接信息
* xhprof为PHP的分层性能测量分析器的配置信息，默认开启性能分析，会根据xhprof.ini的配置，将分析数据写入到指定目录。

####模块配置文件Import.php

每个模块目录下都可以建立一个config文件夹，在其中创建Import.php，写书模块的一些特殊配置信息，如：

```php

<?php
namespace Index;

use Core\Loader;
use Cache\CachePool;

return array(
    'database_config' => array(
        'driver'    => 'Pdo_mysql',
        'host'      => '127.0.0.1',
        'port'      => 3306,
        'username'  => 'root',
        'password'  => '123123',
        'dbname'    => 'yaf_app',
        'charset'   => 'utf8',
    ),

    'expire'      => 3600,
    'redis'       => function() {
            $storage = CachePool::factory(
                array(
                    'storage'   => 'redis',
                    'namespace' => 'auth:',
                )
            );
            CachePool::register($storage);
            CachePool::get()->setResource(
                array(
                    'host' => '127.0.0.1',
                    'port' => 6379,
                )
            );
            CachePool::get()->getResource();
            return CachePool::get();
    },
);
```

上述配置文件会自动载入，并将其写入到`Yaf\Registry::get('config')`和`Yaf\Registry::get('mount')`两个全局对象中；两者的差别是config代表常量的配置，mount代表可回调的配置（callable），如上述的redis配置为一个匿名函数，当使用时通过`Yaf\Registry::get('mount')->get('redis')`可以得到回调的返回对象，多次`Yaf\Registry::get('mount')->get('redis')`调用只会执行一次匿名函数。

另外`Yaf\Registry::get('config')`是`Yaf\Config\Simple`类的实例对象，`Yaf\Registry::get('mount')`是`MountManager\MountManager`类的实例对象。在进行`Core\Factory::db()`时，`database_config`会覆盖全局配置`database`。


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

注意：需要php在命令行下运行支持exec函数，所以开启worker时先把exec函数打开，启动worker之后再关闭exec函数。
	
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

在上述开启了worker之后，我们就可以入队列与出队列，完成工作任务了。本人在使用php-resque与Yaf时，将Yaf命令行路由分发与php-resque的工作任务(job)结合起来使用，即写消息队列的工作任务代码就和写其他正常的Action一样，只不过controller继承内置的\Core\ServiceJob类。

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
	//......
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

可以看到所有异常都会被捕获，在开启日志队列时，会通过ErrorLog类的errorLog方法进行入队列操作。

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


######5.使用异常日志——触发异常

./application/modules/Index/controllers/Index.php

```php
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
```

看到testLogAction，当访问：

http://backend.phpboy.net/index/index/testlog

就会抛出异常，因为程序找不到与之对应的模板文件。

```
Failed opening template /Users/xudianyang/PhpstormProjects/yaf.app-src/application/Modules/Index/Views/index/testlog.phtml: No such file or directory
```
查看日志表，如果数据库连接信息配置得当，就会出现一条日志信息。

###远程调用(Yar轻量级RPC)

大家都知道，在以前的PHP开源或者闭源程序中，经常我们看到A模块直接加载B模块的Model，从而使得这A、B两个模块之间耦合度高，没有办法拆分或者单独部署，这样整个项目的维护与扩展就越来越困难。为了解决这样的问题，本人采用Yar+Yaf的路由分发实现远程调用，完成模块之间的数据调用，并且我们可以和写普通Action一样导出API，只需controller继承Core\ServiceApi内置类。


#####1.导出API

./application/modules/Index/controllers/Demoapi.php

```php
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
```

导出的API需要正常输出相应的格式数据。

#####2.调用API

./application/modules/Index/conrollers/Index.php

```php
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
```

看到`testYarApiAction`，实例化一个Yar\YarClient类。

其中第一个参数代表导出的API的MVC信息，这里为:

```php
array(
	'module' => 'index',
	'controller' => 'demoapi',
	'action' => 'getdata',
)
```

对应上述导出的API

其中第二个参数代表请求相应的参数，args代表传递的所有参数，format为请求间传递数据的格式，默认为json，还可以为serialize、plain。

最后调用Yar\YarClient类实例对象的api方法，完成请求并返回相应数据。

访问：http://backend.phpboy.net/index/index/testyarapi

输出

```
这是通过远程调用返回的数据(Yar)传递的参数: args => some parameters
```

###XHProf的使用

在开启PHP性能分析时，可以将每次请求的分析数据保存到`xhprof.output_dir`目录中，这里只保存了分析数据，如果要查看相信的分析信息，需要xhprof_html和xhprof_lib，这是一个PHP实现的界面，使得查看XHProf分析结果变得更加容易。更多XHProf的详细介绍：[PHP性能分析工具xhprof介绍、安装、使用说明](http://www.phpboy.net/web/php/839.html)

另外需要说明的是要修改xhprof_lib/utils/xhprof_lib.php中

```php
function xhprof_param_init($params) {
  /* Create variables specified in $params keys, init defaults */
  foreach ($params as $k => $v) {
    switch ($v[0]) {
    case XHPROF_STRING_PARAM:
      $p = xhprof_get_string_param($k, $v[1]);
      break;
    case XHPROF_UINT_PARAM:
      $p = xhprof_get_uint_param($k, $v[1]);
      break;
    case XHPROF_FLOAT_PARAM:
      $p = xhprof_get_float_param($k, $v[1]);
      break;
    case XHPROF_BOOL_PARAM:
      $p = xhprof_get_bool_param($k, $v[1]);
      break;
    default:
      xhprof_error("Invalid param type passed to xhprof_param_init: "
                   . $v[0]);
      exit();
    }

    if ($k === 'run') {
      // 这里需要改动
      //$p = implode(',', array_filter(explode(',', $p), 'ctype_xdigit'));
      $p = implode(',', explode(',', $p));
    }

    // create a global variable using the parameter name.
    $GLOBALS[$k] = $p;
  }
}
```






















