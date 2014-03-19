<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Db\Adapter\Driver\Pdo;

use Db\Adapter\Driver\StatementInterface;
use Db\Adapter\Exception;
use Db\Adapter\Parameters;

class Statement implements StatementInterface
{

	/**
	 * @var \PDO
	 */
	protected $pdo = null;

	/**
	 * @var Pdo
	 */
	protected $driver = null;

	/**
	 * @var string
	 */
	protected $sql = '';

	/**
	 * @var boolean
	 */
	protected $isQuery = null;

	/**
	 * @var Parameters
	 */
	protected $parameters = null;

	/**
	 * @var bool
	 */
	protected $parametersBound = false;

	/**
	 * @var \PDOStatement
	 */
	protected $resource = null;

	/**
	 * @var boolean
	 */
	protected $isPrepared = false;

	/**
	 * Set driver
	 *
	 * @param  Pdo $driver
	 * @return $this
	 */
	public function setDriver(Pdo $driver)
	{
		$this->driver = $driver;
		return $this;
	}

	/**
	 * Initialize
	 *
	 * @param  \PDO $connectionResource
	 * @return $this
	 */
	public function initialize(\PDO $connectionResource)
	{
		$this->pdo = $connectionResource;
		return $this;
	}

	/**
	 * Set resource
	 *
	 * @param  \PDOStatement $pdoStatement
	 * @return $this
	 */
	public function setResource(\PDOStatement $pdoStatement)
	{
		$this->resource = $pdoStatement;
		return $this;
	}

	/**
	 * Get resource
	 *
	 * @return mixed
	 */
	public function getResource()
	{
		return $this->resource;
	}

	/**
	 * Set sql
	 *
	 * @param string $sql
	 * @return $this
	 */
	public function setSql($sql)
	{
		$this->sql = $sql;
		return $this;
	}

	/**
	 * Get sql
	 *
	 * @return string
	 */
	public function getSql()
	{
		return $this->sql;
	}

	/**
	 * @param Parameters $parameters
	 * @return $this
	 */
	public function setParameters(Parameters $parameters)
	{
		$this->parameters = $parameters;
		return $this;
	}

	/**
	 * @return Parameters
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @param string $sql
	 * @throws Exception\RuntimeException
	 */
	public function prepare($sql = null)
	{
		if ($this->isPrepared) {
			throw new Exception\RuntimeException('This statement has been prepared already');
		}

		if ($sql == null) {
			$sql = $this->sql;
		}

		$this->resource = $this->pdo->prepare($sql);

		if ($this->resource === false) {
			$error = $this->pdo->errorInfo();
			throw new Exception\RuntimeException($error[2]);
		}

		$this->isPrepared = true;
	}

	/**
	 * @return bool
	 */
	public function isPrepared()
	{
		return $this->isPrepared;
	}

	/**
	 * @param null|array|Parameters $parameters
	 * @throws Exception\InvalidQueryException
	 * @return Result
	 */
	public function execute($parameters = null)
	{
		if (!$this->isPrepared) {
			$this->prepare();
		}

		if (!$this->parameters instanceof Parameters) {
			if ($parameters instanceof Parameters) {
				$this->parameters = $parameters;
				$parameters = null;
			} else {
				$this->parameters = new Parameters();
			}
		}

		if (is_array($parameters)) {
			$this->parameters->setFromArray($parameters);
		}

		if ($this->parameters->count() > 0) {
			$this->bindParameters();
		}

		try {
			$this->resource->execute();
		} catch (\PDOException $e) {
            $code = $e->errorInfo[1];
            $message = $e->errorInfo[2] ?: $e->getMessage();

            if (in_array($code, array(1060, 1061, 1062))) {
                throw new Exception\DuplicateException($message, $code, $e);
            }

			throw new Exception\InvalidQueryException($message, $code, $e);
		}

		$result = $this->driver->createResult($this->resource, $this);
		return $result;
	}

	/**
	 * Bind parameters from container
	 */
	protected function bindParameters()
	{
		if ($this->parametersBound) {
			return;
		}

		$parameters = $this->parameters->getNamedArray();
		foreach ($parameters as $name => &$value) {
			$type = \PDO::PARAM_STR;
			if ($this->parameters->offsetHasErrata($name)) {
				switch ($this->parameters->offsetGetErrata($name)) {
					case Parameters::TYPE_INTEGER:
						$type = \PDO::PARAM_INT;
						break;
					case Parameters::TYPE_NULL:
						$type = \PDO::PARAM_NULL;
						break;
					case Parameters::TYPE_LOB:
						$type = \PDO::PARAM_LOB;
						break;
					case (is_bool($value)):
						$type = \PDO::PARAM_BOOL;
						break;
				}
			}

			// parameter is named or positional, value is reference
			$parameter = is_int($name) ? ($name + 1) : $name;
			$this->resource->bindParam($parameter, $value, $type);
		}

	}

	/**
	 * Perform a deep clone
	 *
	 * @return Statement A cloned statement
	 */
	public function __clone()
	{
		$this->isPrepared = false;
		$this->parametersBound = false;
		$this->resource = null;
		if ($this->parameters) {
			$this->parameters = clone $this->parameters;
		}
	}

}
