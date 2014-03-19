<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Input;

class Standard implements InputInterface
{
	/**
	 * @var array
	 */
	protected $params = array();

	/**
	 * @var string
	 */
	protected $method = null;

	/**
	 * @param string $key
	 * @param mixed $val
	 * @return $this
	 */
	public function set($key, $val)
	{
		$this->params[$key] = $val;
		return $this;
	}

	/**
	 * @param $key string
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		switch (true) {
			case isset($this->params[$key]):
				return $this->params[$key];
			case isset($_GET[$key]):
				return $_GET[$key];
			case isset($_POST[$key]):
				return $_POST[$key];
			case isset($_SERVER[$key]):
				return $_SERVER[$key];
			case isset($_ENV[$key]):
				return $_ENV[$key];
			default:
				return $default;
		}
	}

	/**
	 * @param $key string
	 * @return bool
	 */
	public function has($key)
	{
		switch (true) {
			case isset($this->params[$key]):
				return true;
			case isset($_GET[$key]):
				return true;
			case isset($_POST[$key]):
				return true;
			case isset($_SERVER[$key]):
				return true;
			case isset($_ENV[$key]):
				return true;
			default:
				return false;
		}
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return array_merge($_SERVER, $_POST, $_GET, $this->params);
	}

	/**
	 * Retrieve an external iterator
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->toArray());
	}

	/**
	 * Offset Exists
	 *
	 * @param  string $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return $this->has($offset);
	}

	/**
	 * Offset get
	 *
	 * @param  string $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	/**
	 * Offset set
	 *
	 * @param  string $offset
	 * @param  mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		return $this->set($offset, $value);
	}

	/**
	 * Offset unset
	 *
	 * @param  string $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->params[$offset]);
	}

	/**
	 * Retrieve a member of the $_SERVER superglobal
	 * If no $key is passed, returns the entire $_SERVER array.
	 *
	 * @param string $key
	 * @param mixed $default Default value to use if key not found
	 * @return mixed Returns null if key does not exist
	 */
	public function getServer($key = null, $default = null)
	{
		if (null === $key) {
			return $_SERVER;
		}
		return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
	}

	/**
	 * Return the method by which the request was made
	 *
	 * @return string
	 */
	public function getMethod()
	{
		if (!$this->method) {
			$this->method = strtoupper($this->getServer('REQUEST_METHOD'));
		}
		return $this->method;
	}
}