<?php
/**
 * Api.cloud
 *
 * @copyright Copyright (c) 2013 Beijing CmsTop Technology Co.,Ltd. (http://www.cmstop.com)
 */

namespace Db\Sql;

use Db\Adapter\Driver\DriverInterface;
use Db\Adapter\Driver\StatementInterface;
use Db\Adapter\Parameters;
use Db\Adapter\Platform\PlatformInterface;
use Db\Adapter\Platform\Sql92;

class Select extends AbstractSql
{
	/**#@+
	 * Constant
	 *
	 * @const
	 */
	const SELECT = 'select';
	const QUANTIFIER = 'quantifier';
	const COLUMNS = 'columns';
	const TABLE = 'table';
	const JOINS = 'joins';
	const WHERE = 'where';
	const GROUP = 'group';
	const HAVING = 'having';
	const ORDER = 'order';
	const LIMIT = 'limit';
	const OFFSET = 'offset';
	const QUANTIFIER_DISTINCT = 'DISTINCT';
	const QUANTIFIER_ALL = 'ALL';
	const JOIN_INNER = 'inner';
	const JOIN_OUTER = 'outer';
	const JOIN_LEFT = 'left';
	const JOIN_RIGHT = 'right';
	const SQL_STAR = '*';
	const ORDER_ASCENDING = 'ASC';
	const ORDER_DESCENDING = 'DESC';
    const ALL = 'all';
	/**#@-*/

	/**
	 * @var array Specifications
	 */
	protected $specifications = array(
		self::SELECT => array(
			'SELECT %1$s FROM %2$s' => array(
				array(1 => '%1$s', 2 => '%1$s AS %2$s', 'combinedby' => ', '),
				null
			),
			'SELECT %1$s %2$s FROM %3$s' => array(
				null,
				array(1 => '%1$s', 2 => '%1$s AS %2$s', 'combinedby' => ', '),
				null
			),
		),
		self::JOINS => array(
			'%1$s' => array(
				array(3 => '%1$s JOIN %2$s ON %3$s', 'combinedby' => ' ')
			)
		),
		self::WHERE => 'WHERE %1$s',
		self::GROUP => array(
			'GROUP BY %1$s' => array(
				array(1 => '%1$s', 'combinedby' => ', ')
			)
		),
		self::HAVING => 'HAVING %1$s',
		self::ORDER => array(
			'ORDER BY %1$s' => array(
				array(1 => '%1$s', 2 => '%1$s %2$s', 'combinedby' => ', ')
			)
		),
		self::LIMIT => 'LIMIT %1$s',
		self::OFFSET => 'OFFSET %1$s'
	);

	/**
	 * @var bool
	 */
	protected $tableReadOnly = false;

	/**
	 * @var array|TableIdentifier
	 */
	protected $table = null;

	/**
	 * @var null|string|Expression
	 */
	protected $quantifier = null;

	/**
	 * @var array
	 */
	protected $columns = array(self::SQL_STAR);

	/**
	 * @var array
	 */
	protected $joins = array();

	/**
	 * @var Where
	 */
	protected $where = null;

	/**
	 * @var null|string
	 */
	protected $order = array();

	/**
	 * @var null|array
	 */
	protected $group = null;

	/**
	 * @var null|string|array
	 */
	protected $having = null;

	/**
	 * @var int|null
	 */
	protected $limit = null;

	/**
	 * @var int|null
	 */
	protected $offset = null;

	/**
	 * Constructor
	 *
	 * @param  string|array|TableIdentifier $table
	 */
	public function __construct($table = null)
	{
		if ($table) {
			$this->from($table);
			$this->tableReadOnly = true;
		}

		$this->where = new Where;
		$this->having = new Having;
	}

	/**
	 * Create from clause
	 *
	 * @param  string|array|TableIdentifier $table
	 * @throws Exception\InvalidArgumentException
	 * @return $this
	 */
	public function from($table)
	{
		if ($this->tableReadOnly) {
			throw new Exception\InvalidArgumentException('Since this object was created with a table, use join instead.');
		}

		if (!is_string($table) && !is_array($table) && !$table instanceof TableIdentifier) {
			throw new Exception\InvalidArgumentException('$table must be a string, array, or an instance of TableIdentifier');
		}

		if (is_array($table) && (!is_string(key($table)) || count($table) !== 1)) {
			throw new Exception\InvalidArgumentException('from() expects $table as an array is a single element associative array');
		}

		$this->table = $table instanceof TableIdentifier || is_array($table)
			? $table : new TableIdentifier($table);

		return $this;
	}

	/**
	 * @param string|Expression $quantifier DISTINCT|ALL
	 * @return $this
	 */
	public function quantifier($quantifier)
	{
		if (!is_string($quantifier) && !$quantifier instanceof Expression) {
			throw new Exception\InvalidArgumentException(
				'Quantifier must be one of DISTINCT, ALL, or some platform specific Expression object'
			);
		}
		$this->quantifier = $quantifier;
		return $this;
	}

	/**
	 * Specify columns from which to select
	 * Possible valid states:
	 *   array(*)
	 *   array(value, ...)
	 *     value can be strings or Expression objects
	 *   array(string => value, ...)
	 *     key string will be use as alias,
	 *     value can be string or Expression objects
	 *   "column1, expr() as column2"
	 *
	 * @param  string|Expression|array $columns
	 * @return $this
	 */
	public function columns($columns)
	{
		if (is_string($columns)) {
			$columns = $this->splitColumns($columns);
		} elseif (!is_array($columns)) {
			$columns = array($columns);
		}
		$this->columns = $columns;
		return $this;
	}

	/**
	 * Support multi columns split with comma
	 *
	 * @param string $columns
	 * @return array
	 */
	protected function splitColumns($columns)
	{
		$parts = array();
		$offset = 0;
		$inQuote = null;
		$braceCount = 0;
		while (preg_match('/[\'"()\\\,]/', $columns, $m, PREG_OFFSET_CAPTURE, $offset)) {

			$char = $m[0][0];
			$offset = $m[0][1] + 1;

			switch (true) {
				case $char == '\\':
					$offset++; // eat a char
					break;
				case $inQuote != null:
					if ($inQuote == $char) {
						$inQuote = null;
					}
					break;
				case $char == '"' || $char == "'":
					$inQuote = $char;
					break;
				case $char == '(':
					++$braceCount;
					break;
				case $char == ')':
					if (--$braceCount < 0) { // ignore match error
						$braceCount = 0;
					}
					break;
				case $braceCount < 1 && $char == ',':
					$parts[] = trim(substr($columns, 0, $offset - 1));
					$columns = trim(substr($columns, $offset));
					$offset = 0;
					$braceCount = 0;
					break;
			}
		}
		$parts[] = $columns;
		return array_filter($parts);
	}

	/**
	 * Create join clause
	 *
	 * @param  string|array|TableIdentifier $name
	 * @param  string $on
	 * @param  string|array $columns
	 * @param  string $type one of the JOIN_* constants
	 * @throws Exception\InvalidArgumentException
	 * @return $this
	 */
	public function join($name, $on, $columns = self::SQL_STAR, $type = self::JOIN_INNER)
	{
		if (is_array($name) && (!is_string(key($name)) || count($name) !== 1)) {
			throw new Exception\InvalidArgumentException(
				sprintf("join() expects '%s' as an array is a single element associative array",
					array_shift($name))
			);
		}
		if (empty($columns)) {
			$columns = null;
		} elseif (is_string($columns)) {
			$columns = $this->splitColumns($columns);
		} elseif (!is_array($columns)) {
			$columns = array($columns);
		}
		$this->joins[] = array(
			'name' => $name instanceof TableIdentifier || is_array($name) ? $name :
				new TableIdentifier($name),
			'on' => $on,
			'columns' => $columns,
			'type' => $type
		);
		return $this;
	}

	public function leftJoin($name, $on, $columns = self::SQL_STAR)
	{
		return $this->join($name, $on, $columns, self::JOIN_LEFT);
	}

	public function rightJoin($name, $on, $columns = self::SQL_STAR)
	{
		return $this->join($name, $on, $columns, self::JOIN_RIGHT);
	}

	public function innerJoin($name, $on, $columns = self::SQL_STAR)
	{
		return $this->join($name, $on, $columns, self::JOIN_INNER);
	}

	public function outerJoin($name, $on, $columns = self::SQL_STAR)
	{
		return $this->join($name, $on, $columns, self::JOIN_OUTER);
	}

	/**
	 * Create where clause
	 *
	 * @param  Where|\Closure|string|array|Predicate\PredicateInterface $predicate
	 * @param  string $combination One of the OP_* constants from Predicate\PredicateSet
	 * @throws Exception\InvalidArgumentException
	 * @return $this
	 */
	public function where($predicate, $combination = Predicate\PredicateSet::OP_AND)
	{
		if ($predicate instanceof Where) {
			$this->where = $predicate;
		} elseif ($predicate instanceof Predicate\PredicateInterface) {
			$this->where->addPredicate($predicate, $combination);
		} elseif ($predicate instanceof \Closure) {
			$predicate($this->where);
		} else {
			if (is_string($predicate)) {
				// String $predicate should be passed as an expression
				$predicate = (strpos($predicate, Expression::PLACEHOLDER) !== false)
					? new Predicate\Expression($predicate) : new Predicate\Literal($predicate);
				$this->where->addPredicate($predicate, $combination);
			} elseif (is_array($predicate)) {

				foreach ($predicate as $pkey => $pvalue) {
					if (is_string($pkey)) {
						if (strpos($pkey, '?') !== false) {
							$predicate = new Predicate\Expression($pkey, $pvalue);
						} elseif ($pvalue === null) {
							// map PHP null to SQL IS NULL expression
							$predicate = new Predicate\IsNull($pkey, $pvalue);
						} elseif (is_array($pvalue) || $pvalue instanceof self) {
							// if the value is an array, assume IN() is desired
							$predicate = new Predicate\In($pkey, $pvalue);
						} elseif ($pvalue instanceof Predicate\PredicateInterface) {
							throw new Exception\InvalidArgumentException(
								'Using Predicate must not use string keys'
							);
						} else {
							// otherwise assume that array('foo' => 'bar') means "foo" = 'bar'
							$predicate = new Predicate\Operator($pkey, $pvalue, Predicate\Operator::OP_EQ);
						}
					} elseif ($pvalue instanceof Predicate\PredicateInterface) {
						// Predicate type is ok
						$predicate = $pvalue;
					} else {
						// must be an array of expressions (with int-indexed array)
						$predicate = (strpos($pvalue, Expression::PLACEHOLDER) !== false)
							? new Predicate\Expression($pvalue) : new Predicate\Literal($pvalue);
					}
					$this->where->addPredicate($predicate, $combination);
				}
			}
		}
		return $this;
	}

	public function group($group)
	{
		if (is_array($group)) {
			foreach ($group as $o) {
				$this->group[] = $o;
			}
		} else {
			$this->group[] = $group;
		}
		return $this;
	}

	/**
	 * Create where clause
	 *
	 * @param  Where|\Closure|string|array $predicate
	 * @param  string $combination One of the OP_* constants from Predicate\PredicateSet
	 * @return $this
	 */
	public function having($predicate, $combination = Predicate\PredicateSet::OP_AND)
	{
		if ($predicate instanceof Having) {
			$this->having = $predicate;
		} elseif ($predicate instanceof \Closure) {
			$predicate($this->having);
		} else {
			if (is_string($predicate)) {
				$predicate = new Predicate\Expression($predicate);
				$this->having->addPredicate($predicate, $combination);
			} elseif (is_array($predicate)) {
				foreach ($predicate as $pkey => $pvalue) {
					if (is_string($pkey)) {
						if (strpos($pkey, '?') !== false) {
							$predicate = new Predicate\Expression($pkey, $pvalue);
						} else {
							$predicate = new Predicate\Operator($pkey, $pvalue, Predicate\Operator::OP_EQ);
						}

					} else {
						$predicate = new Predicate\Expression($pvalue);
					}
					$this->having->addPredicate($predicate, $combination);
				}
			}
		}
		return $this;
	}

	/**
	 * @param string|array $order
	 * @return $this
	 */
	public function order($order)
	{
		if (is_string($order)) {
			if (strpos($order, ',') !== false) {
				$order = preg_split('#,\s+#', $order);
			} else {
				$order = (array)$order;
			}
		} elseif (!is_array($order)) {
			$order = array($order);
		}
		foreach ($order as $k => $v) {
			if (is_string($k)) {
				$this->order[$k] = $v;
			} else {
				$this->order[] = $v;
			}
		}
		return $this;
	}

	/**
	 * @param int $limit
	 * @return $this
	 */
	public function limit($limit)
	{
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @param int $offset
	 * @return $this
	 */
	public function offset($offset)
	{
		$this->offset = $offset;
		return $this;
	}

	/**
	 * @param string $part
	 * @return $this
	 * @throws Exception\InvalidArgumentException
	 */
	public function reset($part = self::ALL)
	{

		switch ($part) {
			case self::TABLE:
				if ($this->tableReadOnly) {
					throw new Exception\InvalidArgumentException(
						'Since this object was created with a table and/or schema in the constructor, it is read only.'
					);
				}
				$this->table = null;
				break;
			case self::QUANTIFIER:
				$this->quantifier = null;
				break;
			case self::COLUMNS:
				$this->columns = array();
				break;
			case self::JOINS:
				$this->joins = array();
				break;
			case self::WHERE:
				$this->where = new Where;
				break;
			case self::GROUP:
				$this->group = null;
				break;
			case self::HAVING:
				$this->having = new Having;
				break;
			case self::LIMIT:
				$this->limit = null;
				break;
			case self::OFFSET:
				$this->offset = null;
				break;
			case self::ORDER:
				$this->order = null;
				break;
            case self::ALL:
				if ($this->tableReadOnly) {
                    throw new Exception\InvalidArgumentException(
                        'Since this object was created with a table and/or schema in the constructor, it is read only.'
                    );
                }
				$this->table = null;
                $this->quantifier = null;
                $this->joins = array();
                $this->where = new Where;
                $this->group = null;
                $this->having = new Having;
                $this->limit = null;
                $this->offset = null;
                $this->order = null;
                break;
		}
		return $this;
	}

	public function setSpecification($index, $specification)
	{
		if (!method_exists($this, 'process' . $index)) {
			throw new Exception\InvalidArgumentException('Not a valid specification name.');
		}
		$this->specifications[$index] = $specification;
		return $this;
	}

	public function getRawState($key = null)
	{
		$rawState = array(
			self::TABLE => $this->table,
			self::QUANTIFIER => $this->quantifier,
			self::COLUMNS => $this->columns,
			self::JOINS => $this->joins,
			self::WHERE => $this->where,
			self::ORDER => $this->order,
			self::GROUP => $this->group,
			self::HAVING => $this->having,
			self::LIMIT => $this->limit,
			self::OFFSET => $this->offset
		);
		return (isset($key) && array_key_exists($key, $rawState)) ? $rawState[$key] : $rawState;
	}

	/**
	 * Prepare statement
	 *
	 * @param PlatformInterface|null $platform
	 * @param DriverInterface|null $driver
	 * @return StatementInterface
	 */
	public function prepareStatement(PlatformInterface $platform = null, DriverInterface $driver = null)
	{
		$platform = $platform ?: $this->platform ?: new Sql92;
		$driver = $driver ?: $this->driver;

		$statement = $driver->createStatement();
		$parameters = new Parameters;
		$statement->setParameters($parameters);

		$sqls = array();
		$params = array();

		foreach ($this->specifications as $name => $specification) {
			$params[$name] = $this->{'process' . $name}($platform, $driver, $parameters, $sqls,
				$params);
			if ($specification && is_array($params[$name])) {
				$sqls[$name] = $this->createSqlFromSpecificationAndParameters($specification,
					$params[$name]);
			}
		}

		$sql = implode(' ', $sqls);

		$statement->setSql($sql);
		return $statement;
	}

	/**
	 * Get SQL string for statement
	 *
	 * @param  PlatformInterface $platform If null, defaults to Sql92
	 * @return string
	 */
	public function getSqlString(PlatformInterface $platform = null)
	{
		// get platform, or create default
		$platform = $platform ?: $this->platform ?: new Sql92;

		$sqls = array();
		$params = array();

		foreach ($this->specifications as $name => $specification) {
			$params[$name] = $this->{'process' . $name}($platform, null, null, $sqls, $params);
			if ($specification && is_array($params[$name])) {
				$sqls[$name] = $this->createSqlFromSpecificationAndParameters($specification,
					$params[$name]);
			}
		}

		$sql = implode(' ', $sqls);
		return $sql;
	}

	/**
	 * Returns whether the table is read only or not.
	 *
	 * @return boolean
	 */
	public function isTableReadOnly()
	{
		return $this->tableReadOnly;
	}

	protected function processSelect(PlatformInterface $platform, DriverInterface $driver = null,
		Parameters $parameters = null)
	{
		if (!$this->table) {
			return null;
		}

		$separator = $platform->getIdentifierSeparator();

		$table = $this->table;
		$alias = null;

		if (is_array($table)) {
			$alias = key($table);
			$table = current($table);
		}
		if ($table instanceof TableIdentifier) {
			list($table, $schema, $alias) = $this->table->getAll();
			$table = $platform->quoteIdentifier($table);
			if ($schema) {
				$table = $platform->quoteIdentifier($schema) . $separator . $table;
			}
		} else {
			if ($table instanceof self) {
				$table = '(' . $this->processSubselect($table, $platform, $driver,
					$parameters) . ')';
			} else {
				$table = $platform->quoteIdentifier($table);
			}
		}

		if ($alias) {
			$fromTable = $platform->quoteIdentifier($alias);
			$table .= ' AS ' . $fromTable;
		} else {
			$fromTable = $table;
		}

		$fromTable .= $separator;

		// process table columns
		$columns = array();
		foreach ($this->columns as $columnIndexOrAs => $column) {

			if ($column === self::SQL_STAR) {
				$columns[] = array($fromTable . self::SQL_STAR);
				continue;
			}

			$columnName = array();

			if ($column instanceof Expression) {
				$columnName[] = $this->processExpression(
					$column, $platform, $driver,
					$this->processInfo['paramPrefix'] . ((is_string($columnIndexOrAs)) ?
						$columnIndexOrAs : 'column'),
					$parameters
				);
			} else {
				if (preg_match('/^(.+)\s+as\s+(.+)$/i', $column, $m)) {
					$column = $m[1];
					$columnIndexOrAs = $m[2];
				}
				if (preg_match('/\(.*\)/', $column)) {
					$columnName[] = $column;
				} else {
					$columnName[] = $fromTable . $platform->quoteIdentifier($column);
				}
			}

			if (is_string($columnIndexOrAs)) {
				$columnName[] = $platform->quoteIdentifier($columnIndexOrAs);
			}

			$columns[] = $columnName;
		}


		// process join columns
		foreach ($this->joins as $join) {
			$name = $this->processColumnPrefix($join['name'], $platform);

			if (empty($join['columns'])) {
				continue;
			}
			foreach ($join['columns'] as $jKey => $jColumn) {
				$jColumns = array();
				if ($jColumn instanceof ExpressionInterface) {
					$jColumns[] = $this->processExpression(
						$jColumn, $platform, $driver,
						$this->processInfo['paramPrefix'] . ((is_string($jKey)) ? $jKey : 'column'),
						$parameters
					);
				} else {
					if (preg_match('/^(.+)\s+as\s+(.+)$/i', $jColumn, $m)) {
						$jColumn = $m[1];
						$jKey = $m[2];
					}
					$jColumns[] = $name . $separator . $platform->quoteIdentifierInFragment($jColumn);
				}
				if (is_string($jKey)) {
					$jColumns[] = $platform->quoteIdentifier($jKey);
				}
				$columns[] = $jColumns;
			}
		}

		if ($this->quantifier) {
			if ($this->quantifier instanceof Expression) {
				$quantifier = $this->processExpression($this->quantifier, $platform, $driver,
					'quantifier', $parameters);
			} else {
				$quantifier = $this->quantifier;
			}
		}

		if (isset($quantifier)) {
			return array($quantifier, $columns, $table);
		} else {
			return array($columns, $table);
		}
	}

	/**
	 * @param array|TableIdentifier $name
	 * @param PlatformInterface $platform
	 * @return string
	 */
	protected function processColumnPrefix($name, PlatformInterface $platform)
	{
		$schema = $alias = null;
		if (is_array($name)) {
			$alias = key($name);
			$name = current($name);
		}
		if ($name instanceof TableIdentifier) {
			list ($name, $schema, $alias) = $name->getAll();
		}

		if ($alias) {
			return $platform->quoteIdentifier($alias);
		}

		$name = $platform->quoteIdentifier($name);
		if ($schema) {
			$name = $platform->quoteIdentifier($schema) . $platform->getIdentifierSeparator() . $name;
		}
		return $name;
	}

	protected function processJoins(PlatformInterface $platform, DriverInterface $driver = null,
		Parameters $parameters = null)
	{
		if (!$this->joins) {
			return null;
		}

		$separator = $platform->getIdentifierSeparator();

		// process joins
		$joinSpecArgArray = array();
		foreach ($this->joins as $j => $join) {

			$joinType = strtoupper($join['type']);
			$joinName = $join['name'];
			$joinAs = $joinOn = null;

			// table name
			if (is_array($joinName)) {
				$joinAs = key($joinName);
				$joinName = current($joinName);
			}
			if ($joinName instanceof TableIdentifier) {
				list($joinName, $joinSchema, $joinAs) = $joinName->getAll();
				$joinName = $platform->quoteIdentifier($joinName);
				if ($joinSchema) {
					$joinName = $platform->quoteIdentifier($joinSchema) . $separator . $joinName;
				}
			} else {
				if ($joinName instanceof self) {
					$joinName = '(' . $joinName->processSubSelect($joinName, $platform, $driver,
						$parameters) . ')';
				} else {
					$joinName = $platform->quoteIdentifier($joinName);
				}
			}
			if ($joinAs) {
				$joinName .= ' AS ' . $platform->quoteIdentifier($joinAs);
			}

			if ($join['on'] instanceof ExpressionInterface) {
				$joinOn = $this->processExpression($join['on'], $platform, $driver,
					$this->processInfo['paramPrefix'] . 'join' . ($j + 1) . 'part', $parameters);
			} else {
				$joinOn = $platform->quoteIdentifierInFragment($join['on'], array(
					'=', 'AND', 'OR', '(', ')', 'BETWEEN', '<', '>'
				));
			}

			$joinSpecArgArray[$j] = array($joinType, $joinName, $joinOn);
		}

		return array($joinSpecArgArray);
	}

	protected function processWhere(PlatformInterface $platform, DriverInterface $driver = null,
		Parameters $parameters = null)
	{
		if ($this->where->count() == 0) {
			return null;
		}
		$sql = $this->processExpression(
			$this->where, $platform, $driver,
			$this->processInfo['paramPrefix'] . 'where', $parameters);
		return array($sql);
	}

	protected function processGroup(PlatformInterface $platform, DriverInterface $driver = null,
		Parameters $parameters = null)
	{
		if ($this->group === null) {
			return null;
		}
		// process table columns
		$groups = array();
		foreach ($this->group as $column) {
			$columnSql = '';
			if ($column instanceof Expression) {
				$columnSql .= $this->processExpression(
					$column, $platform, $driver,
					$this->processInfo['paramPrefix'] . 'group', $parameters);
			} else {
				$columnSql .= $platform->quoteIdentifierInFragment($column);
			}
			$groups[] = $columnSql;
		}
		return array($groups);
	}

	protected function processHaving(PlatformInterface $platform, DriverInterface $driver = null,
		Parameters $parameters = null)
	{
		if ($this->having->count() == 0) {
			return null;
		}
		$sql = $this->processExpression(
			$this->having, $platform, $driver,
			$this->processInfo['paramPrefix'] . 'having', $parameters);
		return array($sql);
	}

	protected function processOrder(PlatformInterface $platform, DriverInterface $driver = null,
		Parameters $parameters = null)
	{
		if (empty($this->order)) {
			return null;
		}
		$orders = array();
		foreach ($this->order as $k => $v) {
			if ($v instanceof Expression) {
				$sql = $this->processExpression($v, $platform, $driver, null, $parameters);
				$orders[] = array($sql);
				continue;
			}
			if (is_int($k)) {
				if (strpos($v, ' ') !== false) {
					list($k, $v) = preg_split('# #', $v, 2);
				} else {
					$k = $v;
					$v = self::ORDER_ASCENDING;
				}
			}
			if (strtoupper($v) == self::ORDER_DESCENDING) {
				$orders[] = array($platform->quoteIdentifierInFragment($k), self::ORDER_DESCENDING);
			} else {
				$orders[] = array($platform->quoteIdentifierInFragment($k), self::ORDER_ASCENDING);
			}
		}
		return array($orders);
	}

	protected function processLimit(PlatformInterface $platform, DriverInterface $driver = null,
		Parameters $parameters = null)
	{
		if ($this->limit === null) {
			return null;
		}
		if ($driver) {
			$sql = $driver->formatParameterName('limit');
			$parameters->offsetSet('limit', $this->limit, Parameters::TYPE_INTEGER);
		} else {
			$sql = $platform->quoteLimitOffset($this->limit);
		}

		return array($sql);
	}

	protected function processOffset(PlatformInterface $platform, DriverInterface $driver = null,
		Parameters $parameters = null)
	{
		if ($this->offset === null) {
			return null;
		}
		if ($driver) {
			$parameters->offsetSet('offset', $this->offset, Parameters::TYPE_INTEGER);
			return array($driver->formatParameterName('offset'));
		}

		return array($platform->quoteLimitOffset($this->offset));
	}

	/**
	 * __clone
	 * Resets the where object each time the Select is cloned.
	 *
	 * @return void
	 */
	public function __clone()
	{
		$this->where = clone $this->where;
		$this->having = clone $this->having;
	}
}