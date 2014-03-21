<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Core;

use Yaf\Controller_Abstract;
use Yaf\Dispatcher;
use Sender\Http as SenderHttp;

/**
 * Class ServiceJob
 *
 * 工作任务controller基类
 *
 * @package Core
 */
abstract class ServiceJob extends Controller_Abstract
{
    /**
     * 初始化ServiceJob
     */
    public function init()
    {
        Dispatcher::getInstance()->disableView();
        if (strtolower($this->getRequest()->getMethod()) !== 'cli') {
            $sender = new SenderHttp();
            $sender->setStatus(503);
            $sender->send();
        }
    }
}