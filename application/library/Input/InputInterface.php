<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Input;

interface InputInterface extends \ArrayAccess, \IteratorAggregate
{
	/**
	 * @param $key string
	 * @param $val mixed
	 * @return $this
	 */
	public function set($key, $val);

	/**
	 * @param $key string
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key, $default = null);

	/**
	 * @param $key string
	 * @return bool
	 */
	public function has($key);
}