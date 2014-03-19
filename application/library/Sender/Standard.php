<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */
namespace Sender;

class Standard implements SenderInterface
{
	/**
	 * @var int Status code
	 */
	protected $statusCode = 200;

	/**
	 * @var null|string|resource|callable
	 */
	protected $content = null;

	/**
	 * @var bool
	 */
	protected $sent = false;

	/**
	 * @param $code
	 * @return $this
	 * @throws Exception\InvalidArgumentException
	 */
	public function setStatus($code)
	{
		if (!is_numeric($code)) {
			$code = is_scalar($code) ? $code : gettype($code);
			throw new Exception\InvalidArgumentException(sprintf('Invalid status code provided: "%s"', $code));
		}
		$this->statusCode = (int)$code;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getStatusCode()
	{
		return $this->statusCode;
	}

	/**
	 * @param $content string|resource|callable
	 * @return $this
	 */
	public function setContent($content)
	{
		if (!is_string($content) && !is_callable($content)
			&& !(is_resource($content) && get_resource_type($content) == 'stream'))
		{
			throw new Exception\InvalidArgumentException("Content must be string|stream|callable");
		}
		$this->content = $content;
		return $this;
	}

	/**
	 * @return string|resource|callable
	 */
	public function getContent()
	{
		return $this->content;
	}

	protected function beforeSend()
	{
		if (!headers_sent()) {
			$status = sprintf('Status: %d', $this->getStatusCode());
			header($status);
		}
	}

	/**
	 * Send Response
	 */
	public function send($exit = false)
	{
		if ($this->sent) {
			if ($exit) {
				exit;
			}
			return;
		}

		$lastContent = $this->content;
		$depth = 0;
		while (is_callable($lastContent)) {
			call_user_func($lastContent, $this);
			if ($this->content === $lastContent || ++$depth > 10) {
				throw new Exception\RuntimeException('Bad loop on eval the content');
			}
			$lastContent = $this->content;
		}

		$this->beforeSend();

		if (is_resource($this->content)) {
			fpassthru($this->content);
		} elseif (!is_null($this->content)) {
			echo ((string)$this->content);
		}

		$this->sent = true;
		if ($exit) {
			exit;
		}
	}

	public function __destruct()
	{
		if (is_resource($this->content)) {
			try {
				@fclose($this->content);
			} catch (\Exception $e) {
			}
		}
	}
}