<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Core;

use Yaf\Application;
use Yaf\Controller_Abstract;
use Yaf\Dispatcher;
use Yaf\Request\Http as Request_Http;
use Sender\Http as SenderHttp;
use Output\JsonOutput;
use Output\MsgpackOutput;
use Output\FormatOutput;
use PhpSecure\Crypt\AES;
use Console\Console;

/**
 * Class Service
 *
 * 普通服务的Controller基类
 *
 * @package Core
 */
abstract class Service extends Controller_Abstract
{
    /**
     * @var string
     */
    public $output_format;

    /**
     * @var string
     */
    public $request_http;

    /**
     * Service初始化
     */
    public function init() {
        $this->request_http = new Request_Http();
        $this->output_format = $this->request_http->getPost('format');
        Dispatcher::getInstance()->disableView();
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

    /**
     * 标准响应输出
     *
     * @access protected
     * @param $response 响应正文
     * @param string $format 响应输出数据格式
     * @param int $code 返回的http状态码
     * @return void
     */
    protected function sendHttpOutput($response, $format = 'json', $code = 200) {
        if (is_array($format)) {
            $output_format = $format[1];
            $format = $format[0];
        }
        switch($format) {
            case 'json':
                $content = new JsonOutput($response);
                break;
            case 'msgpack':
                $content = new MsgpackOutput($response);
                break;
            case 'format':
                $content = new FormatOutput($response);
                $content->setFormat($output_format);
                break;
            default:
                $content = new JsonOutput($response);
        }
        $sender = new SenderHttp();
        if ($extra_headers = Console::serializeHeaders()) {
            $sender->getHeaders()->addHeaderLine('HTTP-CCS-FIREPHP', $extra_headers);
        }
        $sender->setStatus($code);
        $content($sender);
    }

    /**
     * 设置标准响应http状态码
     *
     * @access protected
     * @param int $code 返回的http状态码
     * @return void
     */
    protected function sendHttpCode($code = 200)
    {
        $sender = new SenderHttp();
        if ($extra_headers = Console::serializeHeaders()) {
            $sender->getHeaders()->addHeaderLine('HTTP-CCS-FIREPHP', $extra_headers);
        }
        $sender->setStatus($code);
        $sender->send();
    }
}