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

/**
 * Class ErrorLog
 *
 * 日志队列通用类
 *
 * @package Core
 */
class ErrorLog
{
    /**
     * 日志格式字符
     *
     * @access private
     * @var array
     */
    private $log_format_element = array(
        'h' => 'host',
        'u' => 'uri',
        'q' => 'query',
        'm' => 'module',
        'c' => 'controller',
        'a' => 'action',
        'p' => 'params',
        'e' => 'exception',
        'n' => 'code',
        'i' => 'message',
        'f' => 'file',
        'l' => 'line',
        't' => 'timestamp',
        'd' => 'datetime',
    );

    /**
     * 构造方法
     *
     * @access public
     * @param Exception $e
     * @param \Yaf\Request_Abstract $request
     */
    public function __construct(Exception $e, Request_Abstract $request)
    {
        $this->host       = @$_SERVER['SERVER_NAME'];
        $this->uri        = @$_SERVER['REQUEST_URI'];
        $this->query      = @$_SERVER['QUERY_STRING'];
        $this->module     = $request->getModuleName();
        $this->controller = $request->getControllerName();
        $this->action     = $request->getActionName();
        $this->params     = '';
        if ($request->isPost()) {
            $this->params = '$_POST => ' . var_export($request->getPost(), true)."\r\n";
        }
        if (strtolower($request->getMethod()) == 'cli'
            || strtolower($request->getMethod()) == 'api') {
            $this->params .= 'CLI_PARAMS => ' . var_export($request->getParams(), true);
        }
        $this->code       = $e->getCode();
        $this->message    = $e->getMessage();
        $this->file       = $e->getFile();
        $this->line       = $e->getLine();
        $this->exception  = get_class($e);
        $this->datetime   = date('Y-m-d H:i:s');
        $this->timestamp  = time();
    }

    /**
     * 根据格式字符串，返回包含错误日志相应信息的数组格式
     *
     * @access public
     * @param string $format
     * @return array
     */
    public function toArray($format = '')
    {
        if (empty($format)) {
            $format = array_keys($this->log_format_element);
            $format = implode('', $format);
        }

        $result = array();
        $length = strlen($format);

        for ($i = 0; $i < $length; $i++) {
            $key     = $format[$i];
            if (!isset($this->log_format_element[$key])) continue;
            $element = $this->log_format_element[$key];
            $result[$element] = $this->{$element};
        }

        return $result;
    }

    /**
     * 根据格式字符串，返回包含错误日志相应信息的字符串
     *
     * @access public
     * @param string $format
     * @return string
     */
    public function toString($format = '')
    {
        $log = $format;
        foreach($this->log_format_element as $f => $e) {
            $log = str_replace('%'.$f, $this->{$e}, $log);
        }
        $log = str_replace('%%', '%', $log);

        return $log;
    }

    /**
     * 异常日志入队列
     *
     * @access public
     * @return void
     */
    public function errorLog()
    {
        if (isset(Application::app()->getConfig()->application->queue)
            && isset(Application::app()->getConfig()->application->queue->redis)
            && isset(Application::app()->getConfig()->application->queue->log)
        ) {
            $queue = Application::app()->getConfig()->application->queue->toArray();

            $redis_config = $queue['redis'];
            $server   = $redis_config['host'] . ':'. $redis_config['port'];
            $database = isset($redis_config['database']) ? $redis_config['database'] : null;
            Resque::setBackend($server, $database);
            $args = array(
                'module'     => $queue['log']['module'],
                'controller' => $queue['log']['controller'],
                'action'     => $queue['log']['action'],
                'args'       => $this->toArray(),
            );
            $queue_name = Application::app()->getConfig()->application->queue->log->name;
            Resque::enqueue($queue_name, 'Resque\Job\YafCLIRequest', $args, true);
        }
    }
}