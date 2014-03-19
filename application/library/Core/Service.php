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

abstract class Service extends Controller_Abstract
{
    public $output_format, $request_http;

    public function init() {
        $this->request_http = new Request_Http();
        $this->output_format = $this->request_http->getPost('format');
        Dispatcher::getInstance()->disableView();
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