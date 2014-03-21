<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Core;

use Yaf\Loader as InternalLoader;

/**
 * Class Loader
 *
 * 全局手工导入文件类
 *
 * @package Core
 */
final class Loader
{
    /**
     * 导入模块下lib目录下的文件
     *
     * @access public
     * @param string $class_name 包含命名空间的类名称
     * @return bool|void
     */
    static public function lib($class_name)
    {
        if (class_exists($class_name, false)) {
            return true;
        }

        return InternalLoader::import(ROOT_PATH.DS.APP_NAME.DS.'modules'.DS.$class_name.".php");
    }
}