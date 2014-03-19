<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */
namespace Db\Adapter\Driver\Mysqli;

use Db\Adapter\Driver\StatementInterface;
use Db\Adapter\Exception;
use Db\Adapter\Parameters;

class Statement implements StatementInterface
{

	/**
	 * @var \mysqli
	 */
	protected $mysqli = null;

	/**
	 * @var Mysqli
	 */
	protected $driver = null;

	/**
	 * @var string
	 */
	protected $sql = '';

	/**
	 * Parameter container
	 *
	 * @var Parameters
	 */
	protected $parameters = null;

	/**
	 * @var \mysqli_stmt
	 */
	protected $resource = null;

	/**
	 * Is prepared
	 *
	 * @var bool
	 */
	protected $isPrepared = false;

	/**
	 * @var bool
	 */
	protected $bufferResults = false;

	/**
	 * @param  bool $bufferResults
	 */
	public function __construct($bufferResults = false)
	{
		$this->bufferResults = (bool)$bufferResults;
	}

	/**
	 * Set driver
	 *
	 * @param  Mysqli $driver
	 * @return $this
	 */
	public function setDriver(Mysqli $driver)
	{
		$this->driver = $driver;
		return $this;
	}

	/**
	 * Initialize
	 *
	 * @param  \mysqli $mysqli
	 * @return $this
	 */
	public function initialize(\mysqli $mysqli)
	{
		$this->mysqli = $mysqli;
		return $this;
	}

	/**
	 * Set sql
	 *
	 * @param  string $sql
	 * @return $this
	 */
	public function setSql($sql)
	{
		$this->sql = $sql;
		return $this;
	}

	/**
	 * Set Parameter container
	 *
	 * @param Parameters $parameters
	 * @return $this
	 */
	public function setParameters(Parameters $parameters)
	{
		$this->parameters = $parameters;
		return $this;
	}

	/**
	 * Get resource
	 *
	 * @return \mysqli_stmt
	 */
	public function getResource()
	{
		return $this->resource;
	}

	/**
	 * Set resource
	 *
	 * @param  \mysqli_stmt $mysqliStatement
	 * @return $this
	 */
	public function setResource(\mysqli_stmt $mysqliStatement)
	{
		$this->resource = $mysqliStatement;
		$this->isPrepared = true;
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
	 * Get parameters
	 *
	 * @return Parameters
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * Is prepared
	 *
	 * @return bool
	 */
	public function isPrepared()
	{
		return $this->isPrepared;
	}

	/**
	 * Prepare
	 *
	 * @param string $sql
	 * @throws Exception\InvalidQueryException
	 * @throws Exception\RuntimeException
	 * @return $this
	 */
	public function prepare($sql = null)
	{
		if ($this->isPrepared) {
			throw new Exception\RuntimeException('This statement has already been prepared');
		}

		$sql = ($sql) ? : $this->sql;

		$this->resource = $this->mysqli->prepare($this->sql);
		if (!$this->resource instanceof \mysqli_stmt) {
			throw new Exception\InvalidQueryException(
				'Statement couldn\'t be produced with sql: ' . $sql, null,
				new Exception\ErrorException($this->mysqli->error, $this->mysqli->errno)
			);
		}

		$this->isPrepared = true;
		return $this;
	}

	/**
	 * Execute
	 *
	 * @param  null|array|Parameters $parameters
	 * @throws Exception\RuntimeException
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

		$return = $this->resource->execute();

		if ($return === false) {
            if (in_array($this->resource->errno, array(1060, 1061, 1062))) {
                throw new Exception\DuplicateException($this->resource->error, $this->resource->errno);
            }

			throw new Exception\RuntimeException($this->resource->error);
		}

		if ($this->bufferResults === true) {
			$this->resource->store_result();
			$this->isPrepared = false;
			$buffered = true;
		} else {
			$buffered = false;
		}

		$result = $this->driver->createResult($this->resource, $buffered);
		return $result;
	}

	/**
	 * Bind parameters from container
	 *
	 * @return void
	 */
	protected function bindParameters()
	{
		$parameters = $this->parameters->getNamedArray();
		$type = '';
		$args = array();

		foreach ($parameters as $name => &$value) {
			if ($this->parameters->offsetHasErrata($name)) {
				switch ($this->parameters->offsetGetErrata($name)) {
					case Parameters::TYPE_DOUBLE:
						$type .= 'd';
						break;
					case Parameters::TYPE_NULL:
						$value = null; // as per @see http://www.php.net/manual/en/mysqli-stmt.bind-param.php#96148
					case Parameters::TYPE_INTEGER:
						$type .= 'i';
						break;
					case Parameters::TYPE_STRING:
					default:
						$type .= 's';
						break;
				}
			} else {
				$type .= 's';
			}
			$args[] = & $value;
		}

		if ($args) {
			array_unshift($args, $type);
			call_user_func_array(array($this->resource, 'bind_param'), $args);
		}
	}
}
