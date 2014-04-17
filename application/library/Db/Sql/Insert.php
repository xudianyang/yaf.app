<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Db\Sql;

use Db\Adapter\Driver\DriverInterface;
use Db\Adapter\Driver\StatementInterface;
use Db\Adapter\Parameters;
use Db\Adapter\Platform\PlatformInterface;
use Db\Adapter\Platform\Sql92;

class Insert extends AbstractSql
{
	/**#@+
	 * Constants
	 *
	 * @const
	 */
	const SPECIFICATION_INSERT = 'insert';
	const VALUES_MERGE = 'merge';
	const VALUES_SET = 'set';
	/**#@-*/

	/**
	 * @var array Specification array
	 */
	protected $specifications = array(
		self::SPECIFICATION_INSERT => 'INSERT INTO %1$s (%2$s) VALUES (%3$s)'
	);

	/**
	 * @var TableIdentifier
	 */
	protected $table = null;
	protected $columns = array();

	/**
	 * @var array
	 */
	protected $values = array();

	/**
	 * Constructor
	 *
	 * @param  string|TableIdentifier $table
	 */
	public function __construct($table = null)
	{
		if ($table) {
			$this->into($table);
		}
	}

	/**
	 * Crete INTO clause
	 *
	 * @param  string|TableIdentifier $table
	 * @return Insert
	 */
	public function into($table)
	{
		$this->table = $table instanceof TableIdentifier ? $table : new TableIdentifier($table);
		return $this;
	}

	/**
	 * Specify columns
	 *
	 * @param  array $columns
	 * @return Insert
	 */
	public function columns(array $columns)
	{
		$this->columns = $columns;
		return $this;
	}

	/**
	 * Specify values to insert
	 *
	 * @param  array $values
	 * @param  string $flag one of VALUES_MERGE or VALUES_SET; defaults to VALUES_SET
	 * @throws Exception\InvalidArgumentException
	 * @return Insert
	 */
	public function values(array $values, $flag = self::VALUES_SET)
	{
		if ($values == null) {
			throw new \InvalidArgumentException('values() expects an array of values');
		}

		// determine if this is assoc or a set of values
		$keys = array_keys($values);
		$firstKey = current($keys);

		if ($flag == self::VALUES_SET) {
			$this->columns = array();
			$this->values = array();
		}

		if (is_string($firstKey)) {
			foreach ($keys as $key) {
				if (($index = array_search($key, $this->columns)) !== false) {
					$this->values[$index] = $values[$key];
				} else {
					$this->columns[] = $key;
					$this->values[] = $values[$key];
				}
			}
		} elseif (is_int($firstKey)) {
			// determine if count of columns should match count of values
			$this->values = array_merge($this->values, array_values($values));
		}

		return $this;
	}

	public function getRawState($key = null)
	{
		$rawState = array(
			'table' => $this->table,
			'columns' => $this->columns,
			'values' => $this->values
		);
		return (isset($key) && array_key_exists($key, $rawState)) ? $rawState[$key] : $rawState;
	}

	/**
	 * Prepare statement
	 *
	 * @param null|PlatformInterface $platform
	 * @param null|DriverInterface $driver
	 * @return StatementInterface
	 */
	public function prepareStatement(PlatformInterface $platform = null, DriverInterface $driver = null)
	{
		$platform = $platform ?: $this->platform ?: new Sql92;
		$driver = $driver ?: $this->driver;

		$statement = $driver->createStatement();
		$parameters = new Parameters;
		$statement->setParameters($parameters);

		list($table, $schema) = $this->table->getAll();

		$table = $platform->quoteIdentifier($table);

		if ($schema) {
			$table = $platform->quoteIdentifier($schema) . $platform->getIdentifierSeparator() . $table;
		}

		$columns = array();
		$values = array();

		foreach ($this->columns as $cIndex => $column) {
			$columns[$cIndex] = $platform->quoteIdentifier($column);
			if ($this->values[$cIndex] instanceof Expression) {
				$values[$cIndex] = $this->processExpression($this->values[$cIndex], $platform, $driver, null,
					$parameters);
			} else {
				$values[$cIndex] = $driver->formatParameterName($column);
				$parameters->offsetSet($column, $this->values[$cIndex]);
			}
		}

		$sql = sprintf(
			$this->specifications[self::SPECIFICATION_INSERT],
			$table,
			implode(', ', $columns),
			implode(', ', $values)
		);
		$statement->setSql($sql);
		return $statement;
	}

	/**
	 * Get SQL string for this statement
	 *
	 * @param  null|PlatformInterface $platform Defaults to Sql92 if none provided
	 * @return string
	 */
	public function getSqlString(PlatformInterface $platform = null)
	{
		$platform = $platform ?: $this->platform ?: new Sql92;

		list($table, $schema) = $this->table->getAll();

		$table = $platform->quoteIdentifier($table);

		if ($schema) {
			$table = $platform->quoteIdentifier($schema) . $platform->getIdentifierSeparator() . $table;
		}

		$columns = array_map(array($platform, 'quoteIdentifier'), $this->columns);
		$columns = implode(', ', $columns);

		$values = array();
		foreach ($this->values as $value) {
			if ($value instanceof Expression) {
				$values[] = $this->processExpression($value, $platform);
			} elseif ($value === null) {
				$values[] = 'NULL';
			} else {
				$values[] = $platform->quoteValue($value);
			}
		}

		$values = implode(', ', $values);

		return sprintf($this->specifications[self::SPECIFICATION_INSERT], $table, $columns, $values);
	}

	/**
	 * Overloading: variable setting
	 * Proxies to values, using VALUES_MERGE strategy
	 *
	 * @param  string $name
	 * @param  mixed $value
	 * @return Insert
	 */
	public function __set($name, $value)
	{
		$values = array($name => $value);
		$this->values($values, self::VALUES_MERGE);
		return $this;
	}

	/**
	 * Overloading: variable unset
	 * Proxies to values and columns
	 *
	 * @param  string $name
	 * @throws Exception\InvalidArgumentException
	 * @return void
	 */
	public function __unset($name)
	{
		if (($position = array_search($name, $this->columns)) === false) {
			throw new Exception\InvalidArgumentException(
				'The key ' . $name . ' was not found in this objects column list');
		}

		unset($this->columns[$position]);
		unset($this->values[$position]);
	}

	/**
	 * Overloading: variable isset
	 * Proxies to columns; does a column of that name exist?
	 *
	 * @param  string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return in_array($name, $this->columns);
	}

	/**
	 * Overloading: variable retrieval
	 * Retrieves value by column name
	 *
	 * @param  string $name
	 * @throws Exception\InvalidArgumentException
	 * @return mixed
	 */
	public function __get($name)
	{
		if (($position = array_search($name, $this->columns)) === false) {
			throw new Exception\InvalidArgumentException(
				'The key ' . $name . ' was not found in this objects column list');
		}
		return $this->values[$position];
	}
}
