<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Core;

use Yaf\Application;
use Yaf\Dispatcher;
use Yaf\Controller_Abstract;
use PhpSecure\Crypt\AES;
use Exception as Exception;

abstract class ServiceApi extends Controller_Abstract
{
    protected $_output_format;

    public function init()
    {
        Dispatcher::getInstance()->autoRender(true);
        Dispatcher::getInstance()->returnResponse(true);
        Dispatcher::getInstance()->disableView();
        $this->_output_format = $this->getRequest()->getParam('format');
    }

    protected function sendOutput($response, $format = '') {
        if (empty($format)) {
            if ($this->_output_format) {
                $format = $this->_output_format;
            } else {
                $format = 'json';
            }
        }
        if ($response !== null) {
            if (is_object($response) && method_exists($response, 'toArray')) {
                $response = $response->toArray();
            } elseif ($response instanceof \Traversable) {
                $temp = array();
                foreach ($response as $key => $val) {
                    $temp[$key] = $val;
                }
                $response = $temp;
            }
            switch ($format) {
                case 'serialize':
                    $response = "serialize\r\n\r\n".serialize($response);
                    break;
                case 'plain':
                    $response = "plain\r\n\r\n".print_r($response, true);
                    break;
                case 'json': default:
                    $response = "json\r\n\r\n".json_encode($response);
                    break;
            }
        }

        $this->getResponse()->setBody($response, 'content');
    }

    protected function getStderr($error, $format = '') {
        if (empty($format)) {
            if ($this->_output_format) {
                $format = $this->_output_format;
            } else {
                $format = 'json';
            }
        }

        if ($error !== null) {
            if (is_object($error) && method_exists($error, 'toArray')) {
                $error = $error->toArray();
            } elseif ($error instanceof \Traversable) {
                $temp = array();
                foreach ($error as $key => $val) {
                    $temp[$key] = $val;
                }
                $error = $temp;
            }
            switch ($format) {
                case 'serialize':
                    $error = "serialize\r\n\r\n".serialize($error);
                    break;
                case 'plain':
                    $error = "plain\r\n\r\n".print_r($error, true);
                    break;
                case 'json': default:
                    $error = "json\r\n\r\n".json_encode($error);
                break;
            }
        }

        return $error;
    }

    protected function getModule()
    {
        return $this->getRequest()->module;
    }

    protected function getController()
    {
        return $this->getRequest()->controller;
    }

    protected function getAction()
    {
        return $this->getRequest()->action;
    }
}