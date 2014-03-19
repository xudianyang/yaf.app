<?php
namespace Log;

use Yaf\Application;
use Core\Factory;

class LogModel
{
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