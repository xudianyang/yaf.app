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

class Update extends AbstractSql
{
	/**@#++
	 * @const
	 */
	const SPECIFICATION_UPDATE = 'update';
	const SPECIFICATION_WHERE = 'where';

	const VALUES_MERGE = 'merge';
	const VALUES_SET = 'set';
	/**@#-**/

	protected $specifications = array(
		self::SPECIFICATION_UPDATE => 'UPDATE %1$s SET %2$s',
		self::SPECIFICATION_WHERE => 'WHERE %1$s'
	);

	/**
	 * @var TableIdentifier
	 */
	protected $table = '';

	/**
	 * @var bool
	 */
	protected $emptyWhereProtection = true;

	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * @var string|Where
	 */
	protected $where = null;

	/**
	 * Constructor
	 *
	 * @param  string|TableIdentifier $table
	 */
	public function __construct($table = null)
	{
		if ($table) {
			$this->table($table);
		}
		$this->where = new Where();
	}

	/**
	 * Specify table for statement
	 *
	 * @param  string|TableIdentifier $table
	 * @return Update
	 */
	public function table($table)
	{
		$this->table = $table instanceof TableIdentifier ? $table : new TableIdentifier($table);
		return $this;
	}

	/**
	 * Set key/value pairs to update
	 *
	 * @param  array $values Associative array of key values
	 * @param  string $flag One of the VALUES_* constants
	 * @throws Exception\InvalidArgumentException
	 * @return Update
	 */
	public function set(array $values, $flag = self::VALUES_SET)
	{
		if ($values == null) {
			throw new Exception\InvalidArgumentException('set() expects an array of values');
		}

		if ($flag == self::VALUES_SET) {
			$this->data = array();
		}

		foreach ($values as $k => $v) {
			if (!is_string($k)) {
				throw new Exception\InvalidArgumentException('set() expects a string for the value key');
			}
			$this->data[$k] = $v;
		}

		return $this;
	}

	/**
	 * Create where clause
	 *
	 * @param  Where|\Closure|string|array $predicate
	 * @param  string $combination One of the OP_* constants from Predicate\PredicateSet
	 * @throws Exception\InvalidArgumentException
	 * @return Select
	 */
	public function where($predicate, $combination = Predicate\PredicateSet::OP_AND)
	{
		if ($predicate === null) {
			throw new Exception\InvalidArgumentException('Predicate cannot be null');
		}

		if ($predicate instanceof Where) {
			$this->where = $predicate;
		} elseif ($predicate instanceof \Closure) {
			$predicate($this->where);
		} else {
			if (is_string($predicate)) {
				// String $predicate should be passed as an expression
				$predicate = new Predicate\Expression($predicate);
				$this->where->addPredicate($predicate, $combination);
			} elseif (is_array($predicate)) {

				foreach ($predicate as $pkey => $pvalue) {
					// loop through predicates

					if (is_string($pkey)) {
						if (strpos($pkey, '?') !== false) {
							$predicate = new Predicate\Expression($pkey, $pvalue);
						} elseif ($pvalue === null) {
							// map PHP null to SQL IS NULL expression
							$predicate = new Predicate\IsNull($pkey, $pvalue);
						} elseif (is_array($pvalue)) {
							// if the value is an array, assume IN() is desired
							$predicate = new Predicate\In($pkey, $pvalue);
						} else {
							// otherwise assume that array('foo' => 'bar') means "foo" = 'bar'
							$predicate = new Predicate\Operator($pkey, $pvalue, Predicate\Operator::OP_EQ);
						}
					} elseif ($pvalue instanceof Predicate\PredicateInterface) {
						// Predicate type is ok
						$predicate = $pvalue;
					} else {
						// must be an array of expressions (with int-indexed array)
						$predicate = new Predicate\Expression($pvalue);
					}
					$this->where->addPredicate($predicate, $combination);
				}
			}
		}
		return $this;
	}

	public function getRawState($key = null)
	{
		$rawState = array(
			'emptyWhereProtection' => $this->emptyWhereProtection,
			'table' => $this->table,
			'set' => $this->data,
			'where' => $this->where
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

		$data = $this->data;
		if (is_array($data)) {
			$setSql = array();
			foreach ($data as $column => $value) {
				if ($value instanceof Expression) {
					$expr = $this->processExpression($value, $platform, $driver, null, $parameters);
					$setSql[] = $platform->quoteIdentifier($column) . ' = ' . $expr;
				} else {
					$setSql[] = $platform->quoteIdentifier($column) . ' = ' . $driver->formatParameterName($column);
					$parameters->offsetSet($column, $value);
				}
			}
			$data = implode(', ', $setSql);
		}

		$sql = sprintf($this->specifications[self::SPECIFICATION_UPDATE], $table, $data);

		// process where
		if ($this->where->count() > 0) {
			$expr = $this->processExpression($this->where, $platform, $driver, 'where', $parameters);
			$sql .= ' ' . sprintf($this->specifications[self::SPECIFICATION_WHERE], $expr);
		}
		$statement->setSql($sql);
		return $statement;
	}

	/**
	 * Get SQL string for statement
	 *
	 * @param  null|PlatformInterface $platform If null, defaults to Sql92
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

		$data = $this->data;
		if (is_array($data)) {
			$setSql = array();
			foreach ($data as $column => $value) {
				if ($value instanceof Expression) {
					$expr = $this->processExpression($value, $platform);
					$setSql[] = $platform->quoteIdentifier($column) . ' = ' . $expr;
				} elseif ($value === null) {
					$setSql[] = $platform->quoteIdentifier($column) . ' = NULL';
				} else {
					$setSql[] = $platform->quoteIdentifier($column) . ' = ' . $platform->quoteValue($value);
				}
			}
			$data = implode(', ', $setSql);
		}

		$sql = sprintf($this->specifications[self::SPECIFICATION_UPDATE], $table, $data);
		if ($this->where->count() > 0) {
			$expr = $this->processExpression($this->where, $platform, null, 'where');
			$sql .= ' ' . sprintf($this->specifications[self::SPECIFICATION_WHERE], $expr);
		}
		return $sql;
	}

	/**
	 * __clone
	 * Resets the where object each time the Update is cloned.
	 *
	 * @return void
	 */
	public function __clone()
	{
		$this->where = clone $this->where;
	}
}
