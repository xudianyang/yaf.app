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


abstract class ServiceJob extends Controller_Abstract
{
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