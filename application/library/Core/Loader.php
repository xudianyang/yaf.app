<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Core;

use Yaf\Loader as InternalLoader;

class Loader
{
    public static function lib($class_name)
    {
        if (class_exists($class_name, false)) {
            return true;
        }

        return InternalLoader::import(ROOT_PATH.DS.APP_NAME.DS.'modules'.DS.$class_name.".php");
    }
}