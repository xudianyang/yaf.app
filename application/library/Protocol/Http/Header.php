<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Protocol\Http;

class Header
{
	protected $name = null;

	protected $value = null;

	public static function fromString($headerLine)
	{
		list($name, $value) = explode(': ', $headerLine, 2);
		return new static($name, $value);
	}

	public function __construct($name = null, $value = null)
	{
		if ($name) {
			$this->setName($name);
		}

		if ($value) {
			$this->setValue($value);
		}
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set header name
	 *
	 * @param  string $name
	 * @return $this
	 * @throws Exception\InvalidArgumentException
	 */
	public function setName($name)
	{
		if (!is_string($name) || empty($name)) {
			throw new Exception\InvalidArgumentException('Header name must be a string');
		}

		// Pre-filter to normalize valid characters, change underscore to dash
		$name = str_replace(' ', '-', ucwords(str_replace(array('_', '-'), ' ', $name)));

		// Validate what we have
		if (!preg_match('/^[a-z][a-z0-9-]*$/i', $name)) {
			throw new Exception\InvalidArgumentException(
				'Header name must start with a letter, and consist of only letters, numbers, and dashes');
		}

		$this->name = $name;
		return $this;
	}

	/**
	 * Retrieve header field name
	 *
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Set header value
	 *
	 * @param $value
	 * @return $this
	 */
	public function setValue($value)
	{
		$value = (string)$value;

		if (empty($value) || preg_match('/^\s+$/', $value)) {
			$value = '';
		}

		$this->value = $value;
		return $this;
	}

	public function __toString()
	{
		return $this->name . ': ' . $this->value;
	}
}
