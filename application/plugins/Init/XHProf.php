<?php
/**
 * Yaf.app Framework
 *
 * XHProf.php
 *
 * 支持程序的XHProf性能分析插件
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 * @package Init
 */

namespace Init;

use Yaf\Application;
use Yaf\Plugin_Abstract;
use Yaf\Request_Abstract;
use Yaf\Response_Abstract;
use XHProf\Runs\Files as XHProfFiles;

/**
 * Class XHProfPlugin
 * @package Init
 */
class XHProfPlugin extends Plugin_Abstract
{
    /**
     * 在yaf路由分发之后响应正文之前，保存XHProf的性能统计数据
     *
     * @access public
     * @param Request_Abstract $request
     * @param Response_Abstract $response
     * @return void
     */
    public function dispatchLoopShutdown(Request_Abstract $request, Response_Abstract $response)
    {
        if (isset(Application::app()->getConfig()->application->xhprof)) {
            $xhprof_config = Application::app()->getConfig()->application->xhprof->toArray();
            if (extension_loaded('xhprof') &&  $xhprof_config
                && isset($xhprof_config['open']) && $xhprof_config['open'] ) {
                $namespace = $xhprof_config['namespace'] ? $xhprof_config['namespace'] : 'DefaultNameSpace';
                $xhprof_data = xhprof_disable();
                $xhprof_runs = new XHProfFiles();
                $run_id = ucfirst($request->module) . ucfirst($request->controller) . ucfirst($request->action). '-' .str_replace('.', '', (string) microtime(true));
                $xhprof_runs->save_run($xhprof_data, $namespace, $run_id);
            }
        }
    }
}