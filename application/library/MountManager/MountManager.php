<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace MountManager;

class MountManager
{

	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * @var array
	 */
	protected $instances = array();

	/**
	 * @var array
	 */
	protected $factories = array();

	protected function __construct()
	{
	}

	/**
	 * @return self
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param string $name
	 * @param string|callable|object $factory
	 * @return $this
	 * @throws Exception\InvalidArgumentException
	 */
	public function mount($name, $factory)
	{
		if (!is_string($factory) && !is_callable($factory) && !is_object($factory)) {
			throw new Exception\InvalidArgumentException(
				'Provided factory must be a string class name or an callable or instance object.'
			);
		}

		$name = static::normalizeName($name);

		if ($this->has($name)) {
			$this->unmount($name);
		}

		if (!is_callable($factory) && is_object($factory)) {
			$this->instances[$name] = $factory;
		} else {
			$this->factories[$name] = $factory;
		}

		return $this;
	}

	/**
	 * @param $name
	 * @return $this
	 */
	public function unmount($name)
	{
		$name = static::normalizeName($name);
		unset($this->factories[$name]);

		unset($this->instances[$name]);

		return $this;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public function has($name)
	{
		$name = static::normalizeName($name);
		return (isset($this->factories[$name]) || isset($this->instances[$name]));
	}

	/**
	 * @param $name
	 * @return mixed|null
	 * @throws Exception\RuntimeException
	 */
	public function get($name, $parameters = array())
	{
		$name = static::normalizeName($name);

		if (isset($this->instances[$name])) {
			return $this->instances[$name];
		}

		if (!isset($this->factories[$name])) {
			return null;
		}

		$factory = $this->factories[$name];
		$instance = null;

		if (is_string($factory) && class_exists($factory, true)) {
			$instance = new $factory;
		} elseif (is_callable($factory)) {
			try {
                if (empty($parameters)) {
                    $instance = $factory();
                } else {
                    $instance = call_user_func_array($factory, $parameters);
                }
			} catch (\Exception $e) {
				throw new Exception\RuntimeException(
					sprintf('An exception was raised while creating "%s"', $name), $e->getCode(), $e
				);
			}
		}

		if (!$instance) {
			throw new Exception\RuntimeException('The factory was called but did not return an instance.');
		}
		$this->instances[$name] = $instance;

		return $instance;
	}

    public function __get($name) {
        return $this->get($name);
    }

	protected static function normalizeName($name)
	{
		return strtolower(str_replace(array(' ', '-', '_', '\\', '/'), '', $name));
	}
}