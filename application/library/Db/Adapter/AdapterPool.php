<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Db\Adapter;


abstract class AdapterPool
{
	const DEFAULT_ADAPTER = '__DEFAULT__';

	/**
	 * @var AdapterInterface[]
	 */
	protected static $pool = array();

	/**
	 * @param AdapterInterface $adapter
	 * @param string $name
	 */
	public static function register(AdapterInterface $adapter, $name = self::DEFAULT_ADAPTER)
	{
		self::$pool[$name] = $adapter;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public static function has($name)
	{
		return isset(self::$pool[$name]);
	}

	/**
	 * @param string $name
	 * @return null|AdapterInterface
	 */
	public static function get($name = self::DEFAULT_ADAPTER)
	{
		return self::has($name) ? self::$pool[$name] : null;
	}
}