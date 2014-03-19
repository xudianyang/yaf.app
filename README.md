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

$action = $_SERVER['argv'][1];
$parameters = array_slice($_SERVER['argv'], 2);
$params = array();
foreach ($parameters as $parameter) {
    $param = explode('=', $parameter);
    foreach ($param as $one) {
        $params[] = $one;
    }
}

$num_params = count($params);
if ($num_params % 2 != 0) {
    $result = "Setup Wrong Parameter, Please Check!";
    echo $result, "\n";
    return;
}

$run_params = array();
for ($i = 0; $i < $num_params; $i+=2) {
    $param = substr($params[$i], 2);
    $run_params[$param] = $params[$i + 1];
}

if (empty($run_params['queue'])) {
    $run_params['queue'] = '*';
}

$run_params['host']      = isset($run_params['host']) ? $run_params['host'] : '127.0.0.1';
$run_params['port']      = isset($run_params['port']) ? (int)$run_params['port'] : '6379';
$run_params['database']  = isset($run_params['database']) ? (int)$run_params['database'] : 0;
$run_params['log_level'] = isset($run_params['log-level']) ? (boolean)$run_params['log-level'] : false;
$run_params['blocking']  = isset($run_params['blocking']) ? (boolean) $run_params['blocking'] : false;
$run_params['interval']  = isset($run_params['interval']) ? (int) $run_params['interval'] : 1;
$run_params['process']   = isset($run_params['process']) ? (int) $run_params['process'] : 1;
$run_params['pid_path']  = isset($run_params['pid-path']) ? $run_params['pid-path'] : '/tmp/resque/';
extract($run_params);

if ($action == 'start') {
    start();
} else if ($action == 'stop') {
    stop();
}

function start()
{
    global $host, $port, $database, $log_level, $process, $logger, $queue, $pid_path, $interval, $blocking;
    
    Resque::setBackend($host . ':' . $port, $database);
    $logger = new Log($log_level);
    if (isset($prefix)) {
        $logger->log(LogLevel::INFO, 'Prefix Set To {prefix}', array('prefix' => $prefix));
        Redis::prefix($prefix);
    }

    for ($i = 0; $i < $process; ++$i) {
        $pid = Resque::fork();
        if ($pid < 0) {
            $logger->log(LogLevel::EMERGENCY, 'Could Not fork Worker {process}', array('process' => $i));
            $result = 'Running Error!';
            echo $result, "\n";
            return;
        }  else if ($pid == 0) {
            $sid = posix_setsid();
            $queues = explode(',', $queue);
            if ($sid < 0) {
                $logger->log(LogLevel::EMERGENCY, 'Could Not Make The Current Process A Session Leader');
                $result = 'Running Error!';
                echo $result, "\n";
                return;
            }

            if (is_dir($pid_path)) {
                foreach ($queues as $queue) {
                    $pidfile = rtrim($pid_path, DS).DS.$queue."." . posix_getpid();
                    if (!file_put_contents($pidfile, posix_getpid())) {
                        $logger->log(LogLevel::EMERGENCY, 'Could Not Create Pid File, Permission Denied');
                        $result = 'Running Error!';
                        echo $result, "\n";
                        return;
                    }
                }
            } else {
                $logger->log(LogLevel::EMERGENCY, 'Could Not Create Pid File, {pidpath} Directory Not Exists', array('pidpath' => $pid_path));
                $result = 'Running Error!';
                echo $result, "\n";
                return;
            }

            $worker = new Worker($queues);
            $worker->setLogger($logger);
            $logger->log(LogLevel::NOTICE, 'Starting Worker {worker}', array('worker' => $worker));
            $worker->work($interval, $blocking);
            break;
        }
    }
    if (isset($pid) && $pid > 0) {
        exit(0);
    }
}


function stop()
{
    global $queue, $pid_path;
    $queues = explode(',', $queue);
    foreach ($queues as $queue) {
        $pid_files = glob(rtrim($pid_path, DS).DS.$queue."*");
        foreach($pid_files as $pid_file) {
            $pid = file_get_contents($pid_file);
            posix_kill($pid, SIGTERM);
            unlink($pid_file);
        }
    }
}
?>
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

#######示例

```bash

➜ application/library/Resque/bin>php Yaf-cli start --process=5 --queue=log
[notice] Starting Worker xudianyang-mac.lan:2447:log
[notice] Starting Worker xudianyang-mac.lan:2446:log
[notice] Starting Worker xudianyang-mac.lan:2448:log
[notice] Starting Worker xudianyang-mac.lan:2450:log
[notice] Starting Worker xudianyang-mac.lan:2449:log

```


















