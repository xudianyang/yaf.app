<?php
/**
 * Yaf.app Framework
 *
 * Module.php
 *
 * 模块初始化插件，个性化模块配置
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 * @package Init
 */

namespace Init;

use Yaf\Plugin_Abstract;
use Yaf\Request_Abstract;
use Yaf\Response_Abstract;
use Yaf\Registry;
use Yaf\Config\Simple;
use MountManager\MountManager;

/**
 * Class ModulePlugin
 * @package Init
 */
class ModulePlugin extends Plugin_Abstract
{
    /**
     * yaf路由分发之前触发，完成读取模块自定义配置
     *
     * @access public
     * @param \Yaf\Request_Abstract $request
     * @param \Yaf\Response_Abstract $response
     * @return void
     */
    public function preDispatch(Request_Abstract $request, Response_Abstract $response)
    {
        $module_dir = ROOT_PATH.DS.APP_NAME.DS.'modules'.DS.$request->module.DS;
        do {
            if (!file_exists($module_dir.'config'.DS.'Import.php')) break;

            $module_config = include $module_dir.'config'.DS.'Import.php';
            if (!is_array($module_config)) break;

            $mount  = MountManager::getInstance();
            $config = array();
            foreach($module_config as $name => $option) {
                if(!is_string($option) && !is_callable($option) && !is_object($option)) {
                    $config[$name] = $option;
                } else {
                    if (is_string($option)) {
                        $config[$name] = $option;
                    } else {
                        $mount->mount($name, $option);
                    }
                }
            }
            $simple_config = new Simple($config, true);
            Registry::set("config", $simple_config);
            Registry::set("mount", $mount);
        } while(0);
    }

}