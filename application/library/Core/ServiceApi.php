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

/**
 * Class ServiceApi
 *
 * 导出API的controller基类
 *
 * @package Core
 */
abstract class ServiceApi extends Controller_Abstract
{
    /**
     * @var string
     */
    protected $_output_format;

    /**
     * ServiceApi初始化
     */
    public function init()
    {
        Dispatcher::getInstance()->returnResponse(true);
        Dispatcher::getInstance()->disableView();
        $this->_output_format = $this->getRequest()->getParam('format');
    }

    /**
     * 标准响应输出
     *
     * @access protected
     * @param mixed $response
     * @param string $format
     * @return void
     */
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

    /**
     * 标准响应错误输出
     *
     * @access protected
     * @param mixed $error
     * @param string $format
     * @return mixed
     */
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

    /**
     * 返回当前模块名
     *
     * @access protected
     * @return string
     */
    protected function getModule()
    {
        return $this->getRequest()->module;
    }

    /**
     * 返回当前控制器名
     *
     * @access protected
     * @return string
     */
    protected function getController()
    {
        return $this->getRequest()->controller;
    }

    /**
     * 返回当前动作名
     *
     * @access protected
     * @return string
     */
    protected function getAction()
    {
        return $this->getRequest()->action;
    }
}