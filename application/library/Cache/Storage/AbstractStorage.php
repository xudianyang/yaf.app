<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */
namespace Cache\Storage;

use Cache\Exception;

abstract class AbstractStorage implements StorageInterface
{
	/**
	 * Namespace option
	 *
	 * @var string
	 */
	protected $namespace = 'topcache:';

	/**
	 * TTL option
	 *
	 * @var int|float 0 means infinite or maximum
	 */
	protected $ttl = 0;

	/**
	 * @param  array|\Traversable|null $options
	 */
	public function __construct($options = null)
	{
		if (null !== $options) {
			$this->setOptions($options);
		}
	}

	/**
	 * @param  array|\Traversable $options
	 * @throws Exception\InvalidArgumentException
	 * @return $this
	 */
	public function setOptions($options)
	{
		if (!is_array($options) && !$options instanceof \Traversable) {
			throw new Exception\InvalidArgumentException(sprintf(
				'Parameter provided to %s must be an array or Traversable',
				__METHOD__
			));
		}

		foreach ($options as $key => $value) {
			$this->setOption($key, $value);
		}
		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function setOption($key, $value)
	{
		$setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
		if (!method_exists($this, $setter)) {
			throw new Exception\RuntimeException(sprintf(
				'The option "%s" does not have a matching "%s" setter method which must be defined',
				$key, $setter
			));
		}
		$this->{$setter}($value);
	}

	/**
	 * @param int|float $ttl
	 * @throws Exception\InvalidArgumentException
	 */
	public function setTtl($ttl)
	{
		if (!is_int($ttl)) {
			$ttl = (float) $ttl;

			// convert to int if possible
			if ($ttl === (float) (int) $ttl) {
				$ttl = (int) $ttl;
			}
		}

		if ($ttl < 0) {
			throw new Exception\InvalidArgumentException("TTL can't be negative");
		}
		$this->ttl = $ttl;
	}

	/**
	 * @return float|int
	 */
	public function getTtl()
	{
		return $this->ttl;
	}

	/**
	 * @param string $namespace
	 */
	public function setNamespace($namespace)
	{
		$this->namespace = (string) $namespace;
	}

	/**
	 * @return string
	 */
	public function getNamespace()
	{
		return $this->namespace;
	}
}