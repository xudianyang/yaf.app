<?php

use Yaf\Application;
use Yaf\Bootstrap_Abstract;
use Yaf\Dispatcher;
use Init\ModulePlugin;
use Init\XHProfPlugin;

class Bootstrap extends Bootstrap_Abstract
{
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

    public function _initPlugin(Dispatcher $dispatcher)
    {
        $dispatcher->registerPlugin(new XHProfPlugin());
        $dispatcher->registerPlugin(new ModulePlugin());
    }
}