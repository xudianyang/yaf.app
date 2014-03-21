<?php
/**
 * Yaf.app Framework
 *
 * Log.php
 *
 * 日志队列log模型
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 * @package Demo
 */

namespace Log;

use Yaf\Application;
use Core\Factory;

/**
 * Class LogModel
 * @package Log
 */
class LogModel
{
    /**
     * 写入日志到日志表
     *
     * @access public
     * @param array $log
     * @return bool|int
     */
    public function add($log)
    {
        $tablename = Application::app()->getConfig()->application->queue->log->tablename;
        $table = Factory::table($tablename);
        $table->insert($log);
        $lastInsertValue = $table->getLastInsertValue();
        if ($lastInsertValue) {
            return $lastInsertValue;
        } else {
            return false;
        }
    }
}