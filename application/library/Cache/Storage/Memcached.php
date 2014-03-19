<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */
namespace Cache\Storage;

use Memcached as MemcachedSource;
use Cache\Exception;

class Memcached extends AbstractStorage
{
	/**
	 * @var MemcachedSource|array
	 */
	protected $resource;

	/**
	 * @param array|\ArrayAccess|MemcachedSource $resource
	 * @throws \Cache\Exception\InvalidArgumentException
	 * @return Memcached
	 */
	public function setResource($resource)
	{
		if ($resource instanceof MemcachedSource) {
			if (!$resource->getVersion()) {
				throw new Exception\InvalidArgumentException('Invalid memcached resource');
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

		$host = $port = $weight = $persistent_id = null;
		// array(<host>[, <port>[, <weight> [,<persistent_id>]]])
		if (isset($resource[0])) {
			$host = (string) $resource[0];
			if (isset($resource[1])) {
				$port = (int) $resource[1];
			}
			if (isset($resource[2])) {
				$weight = (string) $resource[2];
			}
			if (isset($resource[3])) {
				$persistent_id = (string) $resource[3];
			}
		}
		// array('host' => <host>[, 'port' => <port>[, 'weight' => <weight>[, 'persistent_id' => <persistent_id>]]])
		elseif (isset($resource['host'])) {
			$host = (string) $resource['host'];
			if (isset($resource['port'])) {
				$port = (int) $resource['port'];
			}
			if (isset($resource['weight'])) {
				$weight = (int) $resource['weight'];
			}
			if (isset($resource['persistent_id'])) {
				$persistent_id = (string) $resource['persistent_id'];
			}
		}

		if (!$host) {
			throw new Exception\InvalidArgumentException('Invalid memcached resource, option "host" must be given');
		}

		$this->resource = array(
			'host' => $host,
			'port' => $port,
			'weight' => $weight,
			'persistent_id' => $persistent_id
		);
	}

	/**
	 * @return MemcachedSource
	 * @throws Exception\RuntimeException
	 */
	public function getResource()
	{
		if (!$this->resource) {
			throw new Exception\RuntimeException('Memcached resource must be set');
		}
		if (!$this->resource instanceof MemcachedSource) {
			if ($this->resource['persistent_id']) {
				$resource = new MemcachedSource($this->resource['persistent_id']);
			} else {
				$resource = new MemcachedSource;
			}
			if (!$resource->addServer($this->resource['host'], $this->resource['port'], $this->resource['weight'])) {
				throw new Exception\RuntimeException(sprintf(
					'Cannot connect to memcache server on %s:%d',
					$this->resource['host'], $this->resource['port']
				));
			}

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
		$memcached = $this->getResource();
		return $memcached->get($this->getNamespace() . $key);
	}

	/**
	 * @param string|array $key
	 * @param mixed $value
	 * @return bool
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

		$memcached = $this->getResource();
		$key = $this->getNamespace() . $key;
        $ttl = $ttl === null ? $this->getTtl() : $ttl;
		if ($ttl > 0) {
			return $memcached->set($key, $value, $ttl);
		} else {
			return $memcached->set($key, $value);
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
		$memcached = $this->getResource();
		return $memcached->delete($this->getNamespace() . $key) > 0;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function touch($key)
	{
		$memcached = $this->getResource();
		$key = $this->getNamespace() . $key;
		if ($this->getTtl() > 0) {
			return $memcached->touch($key, time() + $this->getTtl());
		} else {
			return $memcached->touch($key, 0);
		}
	}

	/**
	 * @param string $key
	 * @param int $value
	 * @return int|bool The new value on success, false on failure
	 */
	public function increment($key, $value)
	{
		$memcached = $this->getResource();
		$key = $this->getNamespace() . $key;
		return $memcached->increment($key, $value);
	}

	/**
	 * @param string $key
	 * @param int $value
	 * @return int|bool The new value on success, false on failure
	 */
	public function decrement($key, $value)
	{
		$memcached = $this->getResource();
		$key = $this->getNamespace() . $key;
		return $memcached->decrement($key, $value);
	}
}