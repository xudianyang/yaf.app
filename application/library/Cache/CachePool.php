<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Cache;


abstract class CachePool
{

	const DEFAULT_STORAGE = '__DEFAULT__';

	/**
	 * @var Storage\StorageInterface[]
	 */
	protected static $pool = array();

	/**
	 * @param array|\ArrayAccess $options
	 * @throws Exception\RuntimeException
	 * @throws Exception\InvalidArgumentException
	 * @return Storage\StorageInterface
	 */
	public static function factory($options)
	{
		if (!is_array($options) || $options instanceof \ArrayAccess) {
			throw new Exception\InvalidArgumentException(
				'The factory needs an associative array or a ArrayAccess object as an argument'
			);
		}

		// instantiate the adapter
		if (!isset($options['storage'])) {
			throw new Exception\InvalidArgumentException('Missing "storage"');
		}
		$storageName = $options['storage'];
		unset($options['storage']);
		$storageName = __NAMESPACE__ . '\\Storage\\' . self::normalizeName($storageName);

		if (!class_exists($storageName, true)) {
			throw new Exception\RuntimeException("Storage class '$storageName' not found");
		}

		if (!is_subclass_of($storageName, __NAMESPACE__ . '\\Storage\\StorageInterface')) {
			throw new Exception\RuntimeException("Storage '$storageName' is not implements StorageInterface");
		}

		/** @var $storage Storage\StorageInterface */
		$storage = new $storageName;

		$storage->setOptions($options);

		return $storage;
	}

	/**
	 * @param Storage\StorageInterface $storage
	 * @param string $name
	 */
	public static function register(Storage\StorageInterface $storage, $name = self::DEFAULT_STORAGE)
	{
		self::$pool[$name] = $storage;
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
	 * @return null|Storage\StorageInterface
	 */
	public static function get($name = self::DEFAULT_STORAGE)
	{
		return self::has($name) ? self::$pool[$name] : null;
	}

	protected static function normalizeName($name)
	{
		$name = str_replace(array('-', '_', '.'), ' ', $name);
		$name = ucwords($name);
		return str_replace(' ', '', $name);
	}

}