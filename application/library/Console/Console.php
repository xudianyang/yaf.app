<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Console;

class Console
{
    static private $_initialized = false;
    static private $_driver;
    static private $_headers = array();

    static function log($string)
    {
        if (!self::$_initialized) {
            if (strstr($_SERVER['HTTP_USER_AGENT'], ' Firefox/')) {
                self::$_driver = Driver\FirePHP::getInstance(true);
            }
        }
        if (!empty(self::$_driver)) {
            self::$_driver->log($string);
        }
    }

    static function service($value)
    {
        self::$_headers[] = $value;
    }

    static function serializeHeaders()
    {
        if (!empty(self::$_headers)) {
            return serialize(self::$_headers);
        }
    }
}