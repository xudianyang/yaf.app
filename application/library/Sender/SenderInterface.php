<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Sender;


interface SenderInterface
{
	/**
	 * @param $content string|resource|callable
	 * @return $this
	 */
	public function setContent($content);

	/**
	 * @return string|resource|callable
	 */
	public function getContent();

	/**
	 * Send response
	 */
	public function send($exit = false);
}