<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Db\Adapter;

class Adapter implements AdapterInterface
{
	/**
	 * Prepare Type Constants
	 */
	const PREPARE_TYPE_POSITIONAL = 'positional';
	const PREPARE_TYPE_NAMED = 'named';

	const FUNCTION_FORMAT_PARAMETER_NAME = 'formatParameterName';
	const FUNCTION_QUOTE_IDENTIFIER = 'quoteIdentifier';
	const FUNCTION_QUOTE_VALUE = 'quoteValue';

	const VALUE_QUOTE_SEPARATOR = 'quoteSeparator';

	/**
	 * @var Driver\DriverInterface
	 */
	protected $driver;

	/**
	 * @var Platform\PlatformInterface
	 */
	protected $platform;

	/**
	 * @var Metadata\MetadataInterface
	 */
	protected $metadata;

	/**
	 * @param Driver\DriverInterface|array $driver
	 * @param Platform\PlatformInterface $platform
	 */
	public function __construct($driver, Platform\PlatformInterface $platform = null)
	{
		// first argument can be an array of parameters
		$parameters = array();

		if (is_array($driver)) {
			$parameters = $driver;
			$driver = $this->createDriver($parameters);
		} elseif (!$driver instanceof Driver\DriverInterface) {
			throw new Exception\InvalidArgumentException(
				'The supplied or instantiated driver object does not implement DriverInterface');
		}

		$driver->checkEnvironment();
		$this->driver = $driver;

		if ($platform == null) {
			$platform = $this->createPlatform($parameters);
		}

		$this->platform = $platform;

		$this->metadata = $this->createMetadata($platform->getName());
	}

	/**
	 * Get driver
	 *
	 * @throws Exception\RuntimeException
	 * @return Driver\DriverInterface
	 */
	public function getDriver()
	{
		if ($this->driver == null) {
			throw new Exception\RuntimeException('Driver has not been set or configured for this adapter.');
		}
		return $this->driver;
	}

	/**
	 * @return Platform\PlatformInterface
	 */
	public function getPlatform()
	{
		return $this->platform;
	}

	/**
	 * @return Metadata\MetadataInterface
	 */
	public function getMetadata()
	{
		return $this->metadata;
	}

	/**
	 * Query
	 *
	 * @param string|Driver\StatementInterface $stmt
	 * @param array|Parameters $parameters
	 * @return Driver\ResultInterface
	 */
	public function query($stmt, $parameters = null)
	{
		if (!$stmt instanceof Driver\StatementInterface) {
			$stmt = $this->driver->createStatement($stmt);
		}

		if (is_array($parameters) || $parameters instanceof Parameters) {
			$stmt->setParameters((is_array($parameters)) ? new Parameters($parameters) : $parameters);
		}
		return $stmt->execute();
	}

	/**
	 * Create statement
	 *
	 * @param null|string $sql
	 * @param null|Parameters|array $parameters
	 * @internal param array|\Db\Adapter\Parameters $initialParameters
	 * @return Driver\StatementInterface
	 */
	public function createStatement($sql = null, $parameters = null)
	{
		$statement = $this->driver->createStatement($sql);
		if ($parameters == null || !$parameters instanceof Parameters && is_array($parameters)) {
			$parameters = new Parameters((is_array($parameters) ? $parameters : array()));
		}
		$statement->setParameters($parameters);
		return $statement;
	}

	/**
	 * @param array $parameters
	 * @return Driver\DriverInterface
	 * @throws Exception\InvalidArgumentException
	 */
	protected function createDriver($parameters)
	{
		if (!isset($parameters['driver'])) {
			throw new Exception\InvalidArgumentException(
				__FUNCTION__ . ' expects a "driver" key to be present inside the parameters');
		}

		if ($parameters['driver'] instanceof Driver\DriverInterface) {
			return $parameters['driver'];
		}

		if (!is_string($parameters['driver'])) {
			throw new Exception\InvalidArgumentException(
				__FUNCTION__ . ' expects a "driver" to be a string or instance of DriverInterface');
		}

		$options = array();
		if (isset($parameters['options'])) {
			$options = (array)$parameters['options'];
			unset($parameters['options']);
		}

		$driverName = strtolower($parameters['driver']);
		switch ($driverName) {
			case 'mysqli':
				$driver = new Driver\Mysqli\Mysqli($parameters, null, null, $options);
				break;
			case 'pdo':
			default:
				if ($driverName == 'pdo' || strpos($driverName, 'pdo') === 0) {
					$driver = new Driver\Pdo\Pdo($parameters);
				}
		}

		if (!isset($driver) || !$driver instanceof Driver\DriverInterface) {
			throw new Exception\InvalidArgumentException('DriverInterface expected', null, null);
		}

		return $driver;
	}

	/**
	 * @param array $parameters
	 * @throws Exception\InvalidArgumentException
	 * @return Platform\PlatformInterface
	 */
	protected function createPlatform($parameters)
	{
		if (isset($parameters['platform'])) {
			$platformName = $parameters['platform'];
		} elseif ($this->driver instanceof Driver\DriverInterface) {
			$platformName = $this->driver->getDatabasePlatformName(Driver\DriverInterface::NAME_FORMAT_CAMELCASE);
		} else {
			throw new Exception\InvalidArgumentException(
				'A platform could not be determined from the provided configuration');
		}

		$options = (isset($parameters['platform_options'])) ? $parameters['platform_options'] : array();

		switch ($platformName) {
			case 'Mysql':
				return new Platform\Mysql($options);
			default:
				return new Platform\Sql92($options);
		}
	}

	protected function createMetadata($platformName)
	{
		switch ($platformName) {
			case 'MySQL':
				return new Metadata\Mysql($this);
		}
	}
}
