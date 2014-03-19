<?php

use Core\ServiceJob;
use Log\LogModel;

class IndexJobController extends ServiceJob
{
    public function init()
    {
        parent::init();
    }
    public function indexAction()
    {
        $data = $this->getRequest()->getParams();
        $log = new LogModel();
        $log->add($data);
    }
}