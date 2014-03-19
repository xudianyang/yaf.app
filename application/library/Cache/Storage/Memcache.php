<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Cache\Storage;

use Memcache as MemcacheSource;
use Cache\Exception;

class Memcache extends AbstractStorage
{
	const DEFAULT_PORT = 11211;

	const DEFAULT_WEIGHT = 1;

	const DEFAULT_PERSISTENT = true;

	const DEFAULT_COMPRESSTHRESHOLD = 2000;

	/**
	 * @var MemcacheSource|array
	 */
	protected $resource;

	/**
	 * @param array|\ArrayAccess|MemcacheSource $resource
	 * @throws \Cache\Exception\InvalidArgumentException
	 * @return Memcache
	 */
	public function setResource($resource)
	{
		if ($resource instanceof MemcacheSource) {
			if (!$resource->getVersion()) {
				throw new Exception\InvalidArgumentException('Invalid memcache resource');
			}

			$this->resource = $resource;
			return $this;
		}
		if (is_string($resource)) {
			$resource = array($resource);
		}
		if (!is_array($resource) && !$resource instanceof \ArrayAccess) {
			throw new Exception\InvalidArgumentException(sprintf(
				'%s: expects an string, array, or Traversable argument; received "%s"',
				__METHOD__, (is_object($resource) ? get_class($resource) : gettype($resource))
			));
		}

		$host = $port = $weight = $persistent = null;
		// array(<host>[, <port>[, <weight>[, <persistent>]]])
		if (isset($resource[0])) {
			$host = (string) $resource[0];
			if (isset($resource[1])) {
				$port = (int) $resource[1];
			}
			if (isset($resource[2])) {
				$weight = (int) $resource[2];
			}
			if (isset($resource[3])) {
				$persistent = (bool) $resource[3];
			}
		}
		// array('host' => <host>[, 'port' => <port>[, 'weight' => <weight>[, 'persistent' => <persistent>]]])
		elseif (isset($resource['host'])) {
			$host = (string) $resource['host'];
			if (isset($resource['port'])) {
				$port = (int) $resource['port'];
			}
			if (isset($resource['weight'])) {
				$weight = (int) $resource['weight'];
			}
			if (isset($resource['persistent'])) {
				$persistent = (bool) $resource['persistent'];
			}
		}

		if (!$host) {
			throw new Exception\InvalidArgumentException('Invalid memcache resource, option "host" must be given');
		}

		$this->resource = array(
			'host' => $host,
			'port' => $port === null ? self::DEFAULT_PORT : $port,
			'weight' => $weight <= 0 ? self::DEFAULT_WEIGHT : $weight,
			'persistent' => $persistent === null ? self::DEFAULT_PERSISTENT : $persistent
		);
	}

	/**
	 * @param $threshold
	 * @param null $min_savings default value depends on memcache implement, current is 0.2
	 * @throws Exception\InvalidArgumentException
	 * @return bool
	 */
	public function setCompressThreshold($threshold, $min_savings = null)
	{
		$memcache = $this->getResource();
		if ($min_savings !== null) {
			if (!is_float($min_savings) || $min_savings > 1 || $min_savings < 0) {
				throw new Exception\InvalidArgumentException(
					'Invalid memcache min_savings value, must be a float value between 0 ~ 1');
			}
			return $memcache->setCompressThreshold($threshold, $min_savings);
		}
		return $memcache->setCompressThreshold($threshold);
	}

	/**
	 * @return MemcacheSource
	 * @throws Exception\RuntimeException
	 */
	public function getResource()
	{
		if (!$this->resource) {
			throw new Exception\RuntimeException('Memcache resource must be set');
		}
		if (!$this->resource instanceof MemcacheSource) {
			$resource = new MemcacheSource;
			if (!$resource->addserver($this->resource['host'], $this->resource['port'],
				$this->resource['persistent'], $this->resource['weight'])) {
				throw new Exception\RuntimeException(sprintf(
					'Cannot connect to memcache server on %s:%d',
					$this->resource['host'], $this->resource['port']
				));
			}

			$resource->setCompressThreshold(self::DEFAULT_COMPRESSTHRESHOLD);

			$this->resource = $resource;
		}
		return $this->resource;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		$memcache = $this->getResource();
		return $memcache->get($this->getNamespace() . $key);
	}

    /**
     * @param $key
     * @param null $value
     * @param null $ttl
     * @return bool|mixed
     */
    public function set($key, $value = null, $ttl = null)
	{
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				if ($this->set($k, $v, $ttl) === false) {
					return false;
				}
			}
			return true;
		}

		$memcache = $this->getResource();
		$key = $this->getNamespace() . $key;
        $ttl = $ttl === null ? $this->getTtl() : $ttl;
		if ($ttl > 0) {
			return $memcache->set($key, $value, 0, $ttl);
		} else {
			return $memcache->set($key, $value);
		}
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		return $this->get($key) !== false;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function remove($key)
	{
		$memcache = $this->getResource();
		return $memcache->delete($this->getNamespace() . $key);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function touch($key)
	{
		$memcache = $this->getResource();
		$key = $this->getNamespace() . $key;
		$value = $memcache->get($key);
		if ($value === false) {
			return false;
		}
		if ($this->getTtl() > 0) {
			return $memcache->set($key, $value, 0, time() + $this->getTtl());
		} else {
			return $memcache->set($key, $value);
		}
	}

	/**
	 * @param string $key
	 * @param int $value
	 * @return int|bool The new value on success, false on failure
	 */
	public function increment($key, $value)
	{
		$memcache = $this->getResource();
		$key = $this->getNamespace() . $key;
		return $memcache->increment($key, $value);
	}

	/**
	 * @param string $key
	 * @param int $value
	 * @return int|bool The new value on success, false on failure
	 */
	public function decrement($key, $value)
	{
		$memcache = $this->getResource();
		$key = $this->getNamespace() . $key;
		return $memcache->decrement($key, $value);
	}
}