<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Cache\Storage;

use Redis as RedisSource;
use Cache\Exception;

class Redis extends AbstractStorage
{
	/**
	 * @var RedisSource|array
	 */
	protected $resource;

	/**
	 * @param array|\ArrayAccess|RedisSource $resource
	 */
	public function setResource($resource)
	{
		if ($resource instanceof RedisSource) {
			try {
				$resource->ping();
			} catch (\RedisException $ex) {
				throw new Exception\InvalidArgumentException('Invalid redis resource', $ex->getCode(), $ex);
			}
			if ($resource->getOption(RedisSource::OPT_SERIALIZER) == RedisSource::SERIALIZER_NONE) {
				$resource->setOption(RedisSource::OPT_SERIALIZER, RedisSource::SERIALIZER_PHP);
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

		$host = $port = $auth = null;
		// array(<host>[, <port>[, <auth>]])
		if (isset($resource[0])) {
			list($host, $port) = explode(':', (string) $resource[0]);
			if (isset($resource[1])) {
				$port = (int) $resource[1];
			}
			if (isset($resource[2])) {
				$auth = (string) $resource[2];
			}
		}
		// array('host' => <host>[, 'port' => <port>[, 'auth' => <auth>]])
		elseif (isset($resource['host'])) {
			@list($host, $port) = explode(':', (string) $resource['host']);
			if (isset($resource['port'])) {
				$port = (int) $resource['port'];
			}
			if (isset($resource['auth'])) {
				$auth = (string) $resource['auth'];
			}
		}

		if (!$host) {
			throw new Exception\InvalidArgumentException('Invalid redis resource, option "host" must be given');
		}

		$this->resource = array(
			'host' => $host,
			'port' => $port ?: 6379,
			'auth' => $auth
		);
	}

	/**
	 * @return RedisSource
	 * @throws Exception\RuntimeException
	 */
	public function getResource()
	{
		if (!$this->resource) {
			throw new Exception\RuntimeException('Redis resource must be set');
		}
		if (!$this->resource instanceof RedisSource) {
			$resource = new RedisSource;
			if (!$resource->connect($this->resource['host'], $this->resource['port'])) {
				throw new Exception\RuntimeException(sprintf(
					'Cannot connect to redis server on %s:%d',
					$this->resource['host'], $this->resource['port']
				));
			}
			if (isset($this->resource['auth']) && !$resource->auth($this->resource['auth'])) {
				throw new Exception\RuntimeException(sprintf(
					'Auth failed on %s:%d, auth: %s',
					$this->resource['host'], $this->resource['port'], $this->resource['auth']
				));
			}

			$resource->setOption(RedisSource::OPT_SERIALIZER, RedisSource::SERIALIZER_PHP);

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
		$redis = $this->getResource();
		return $redis->get($this->getNamespace() . $key);
	}

    /**
     * @param array|string $key
     * @param mixed $value
     * @param null $ttl
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

		$redis = $this->getResource();
		$key = $this->getNamespace() . $key;
        $ttl = $ttl === null ? $this->getTtl() : $ttl;
		if ($ttl > 0) {
			return $redis->setex($key, $ttl, $value);
		} else {
			return $redis->set($key, $value);
		}
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		$redis = $this->getResource();
		return $redis->exists($this->getNamespace() . $key);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function remove($key)
	{
		$redis = $this->getResource();
		return $redis->delete($this->getNamespace() . $key) > 0;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function touch($key)
	{
		$redis = $this->getResource();
		$key = $this->getNamespace() . $key;
		if ($this->getTtl() > 0) {
			return $redis->expireAt($key, time() + $this->getTtl());
		} else {
			return $redis->persist($key);
		}
	}

	/**
	 * @param string $key
	 * @param int $value
	 * @return int|bool The new value on success, false on failure
	 */
	public function increment($key, $value)
	{
		$redis = $this->getResource();
		$key = $this->getNamespace() . $key;
		if (is_float($value)) {
			return $redis->incrByFloat($key, $value);
		} else {
			return $redis->incrBy($key, $value);
		}
	}

	/**
	 * @param string $key
	 * @param int $value
	 * @return int|bool The new value on success, false on failure
	 */
	public function decrement($key, $value)
	{
		$redis = $this->getResource();
		$key = $this->getNamespace() . $key;
		if (is_float($value)) {
			return $redis->decrByFloat($key, $value);
		} else {
			return $redis->decrBy($key, $value);
		}
	}
}