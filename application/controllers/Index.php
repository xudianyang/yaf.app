<?php

use Yaf\Controller_Abstract;
use Yaf\Dispatcher;
class IndexController extends Controller_Abstract
{
    public function indexAction()
    {
        Dispatcher::getInstance()->disableView(0);
        echo 'Great,It Works!';
    }
}