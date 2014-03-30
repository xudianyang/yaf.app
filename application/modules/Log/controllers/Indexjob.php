<?php
/**
 * Yaf.app Framework
 *
 * Indexjob.php
 *
 * 日志队列工作任务控制器
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 * @package Log
 */

use Core\ServiceJob;
use Log\LogModel;

/**
 * Class IndexJobController
 * @package Log
 */
final class IndexJobController extends ServiceJob
{
    /**
     * 初始化动作
     * @access public
     */
    public function init()
    {
        parent::init();
    }

    /**
     * 工作任务(job)：index
     *
     * 写日志数据到数据库
     * @access public
     */
    public function indexAction()
    {
        $data = $this->getRequest()->getParams();
        $log = new LogModel();
        $log->add($data);
    }
}