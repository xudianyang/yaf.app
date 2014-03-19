<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Db\Adapter\Driver\Pdo;

use Db\Adapter\Driver\DriverInterface;
use Db\Adapter\Exception;

class Pdo implements DriverInterface
{
	/**
	 * @var Connection
	 */
	protected $connection = null;

	/**
	 * @var Statement
	 */
	protected $statementPrototype = null;

	/**
	 * @var Result
	 */
	protected $resultPrototype = null;

	/**
	 * @param array|Connection|\PDO $connection
	 * @param null|Statement $statementPrototype
	 * @param null|Result $resultPrototype
	 */
	public function __construct($connection, Statement $statementPrototype = null, Result $resultPrototype = null)
	{
		if (!$connection instanceof Connection) {
			$connection = new Connection($connection);
		}

		$this->registerConnection($connection);
		$this->registerStatementPrototype(($statementPrototype) ? : new Statement);
		$this->registerResultPrototype(($resultPrototype) ? : new Result);
	}

	/**
	 * Register connection
	 *
	 * @param  Connection $connection
	 * @return Pdo
	 */
	public function registerConnection(Connection $connection)
	{
		$this->connection = $connection;
		$this->connection->setDriver($this);
		return $this;
	}

	/**
	 * Register statement prototype
	 *
	 * @param Statement $statementPrototype
	 */
	public function registerStatementPrototype(Statement $statementPrototype)
	{
		$this->statementPrototype = $statementPrototype;
		$this->statementPrototype->setDriver($this);
	}

	/**
	 * Register result prototype
	 *
	 * @param Result $resultPrototype
	 */
	public function registerResultPrototype(Result $resultPrototype)
	{
		$this->resultPrototype = $resultPrototype;
	}

	/**
	 * Get database platform name
	 *
	 * @param  string $nameFormat
	 * @return string
	 */
	public function getDatabasePlatformName($nameFormat = self::NAME_FORMAT_CAMELCASE)
	{
		$name = $this->getConnection()->getDriverName();
		if ($nameFormat == self::NAME_FORMAT_CAMELCASE) {
			return ucfirst($name);
		} else {
			switch ($name) {
				case 'mysql':
					return 'MySQL';
				default:
					return ucfirst($name);
			}
		}
	}

	/**
	 * Check environment
	 */
	public function checkEnvironment()
	{
		if (!extension_loaded('PDO')) {
			throw new Exception\RuntimeException(
				'The PDO extension is required for this adapter but the extension is not loaded');
		}
	}

	/**
	 * @return Connection
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * @param string|\PDOStatement $sqlOrResource
	 * @return Statement
	 */
	public function createStatement($sqlOrResource = null)
	{
		$statement = clone $this->statementPrototype;
		if ($sqlOrResource instanceof \PDOStatement) {
			$statement->setResource($sqlOrResource);
		} else {
			if (is_string($sqlOrResource)) {
				$statement->setSql($sqlOrResource);
			}
			if (!$this->connection->isConnected()) {
				$this->connection->connect();
			}
			$statement->initialize($this->connection->getResource());
		}
		return $statement;
	}

	/**
	 * @param \PDOStatement $resource
	 * @param mixed $context
	 * @return Result
	 */
	public function createResult($resource, $context = null)
	{
		$result = clone $this->resultPrototype;
		$result->initialize($resource, $this->connection->getLastGeneratedValue());
		return $result;
	}

	/**
	 * @return array
	 */
	public function getPrepareType()
	{
		return self::PARAMETERIZATION_NAMED;
	}

	/**
	 * @param string $name
	 * @param string|null $type
	 * @return string
	 */
	public function formatParameterName($name, $type = null)
	{
		if ($type == null && !is_numeric($name) || $type == self::PARAMETERIZATION_NAMED) {
			return ':' . $name;
		}

		return '?';
	}

	/**
	 * @return mixed
	 */
	public function getLastGeneratedValue()
	{
		return $this->connection->getLastGeneratedValue();
	}

}
