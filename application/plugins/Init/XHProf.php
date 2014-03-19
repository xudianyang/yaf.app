<?php
namespace Init;

use Yaf\Application;
use Yaf\Plugin_Abstract;
use Yaf\Request_Abstract;
use Yaf\Response_Abstract;
use XHProf\Runs\Files as XHProfFiles;

class XHProfPlugin extends Plugin_Abstract
{
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