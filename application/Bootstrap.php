<?php
/**
 * Yaf.app Framework
 *
 * Bootstrap.php
 *
 * 应用引导文件，初始化插件及设置性能统计
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 * @package Global
 */

use Yaf\Application;
use Yaf\Bootstrap_Abstract;
use Yaf\Dispatcher;
use Init\ModulePlugin;
use Init\XHProfPlugin;

/**
 * Yaf引导类 Class Bootstrap
 *
 * 应用所有需要尽早初始化的操作都需要在这里面定义并自动由yaf框架调用执行
 *
 * @package Global
 */
final class Bootstrap extends Bootstrap_Abstract
{
    /**
     * 读取相应的配置初始化XHProf
     *
     * @access public
     * @param \Yaf\Dispatcher $dispatcher
     * @return void
     */
    public function _initXHProf(Dispatcher $dispatcher)
    {
        if (isset(Application::app()->getConfig()->application->xhprof)) {
            $xhprof_config = Application::app()->getConfig()->application->xhprof->toArray();
            if (extension_loaded('xhprof') &&  $xhprof_config
                && isset($xhprof_config['open']) && $xhprof_config['open'] ) {
                $default_flags = XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY;

                $ignore_functions = isset($xhprof_config['ignored_functions']) && is_array($xhprof_config['ignored_functions'])
                    ? $xhprof_config['ignored_functions']
                    : array();
                if (isset($xhprof_config['flags'])) {
                    xhprof_enable($xhprof_config['flags'], $ignore_functions);
                } else {
                    xhprof_enable($default_flags, $ignore_functions);
                }
            }
        }
    }

    /**
     * 注册插件
     *
     * @access public
     * @param Yaf\Dispatcher $dispatcher
     * @return void
     */
    public function _initPlugin(Dispatcher $dispatcher)
    {
        $dispatcher->registerPlugin(new XHProfPlugin());
        $dispatcher->registerPlugin(new ModulePlugin());
    }
}