<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Db\Sql;

class TableIdentifier
{

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var string
	 */
	protected $schema;

	/**
	 * @var string
	 */
	protected $alias;

	/**
	 * @param string $table
	 * @param string $schema
	 * @param string $alias
	 */
	public function __construct($table, $schema = null, $alias = null)
	{
		if (!preg_match('/^(?:(\w+)\.)?(\w+)(?:\s+(?:as\s+)?(\w+))?$/i', $table, $match)) {
			throw new Exception\InvalidArgumentException('table name is not valid');
		}

        if (!isset($match[3]) && is_null($alias)) {
            $match[3] = '';
        }
		list(, $this->schema, $this->table, $this->alias) = $match;

		if ($schema) {
			$this->schema = $schema;
		}
		if ($alias) {
			$this->alias = $alias;
		}
	}

	/**
	 * @param string $table
	 */
	public function setTable($table)
	{
		$this->table = $table;
	}

	/**
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * @return bool
	 */
	public function hasSchema()
	{
		return ($this->schema != null);
	}

	/**
	 * @param $schema
	 */
	public function setSchema($schema)
	{
		$this->schema = $schema;
	}

	/**
	 * @return string
	 */
	public function getSchema()
	{
		return $this->schema;
	}

	/**
	 * @return bool
	 */
	public function hasAlias()
	{
		return ($this->alias != null);
	}

	/**
	 * @param string $alias
	 */
	public function setAlias($alias)
	{
		$this->alias = $alias;
	}

	/**
	 * @return string
	 */
	public function getAlias()
	{
		return $this->alias;
	}

	/**
	 * @return array
	 */
	public function getAll()
	{
		return array($this->table, $this->schema, $this->alias);
	}

	public function __toString()
	{
		$ret = $this->table;
		if ($this->schema) {
			$ret = $this->schema . '.' . $ret;
		}
		if ($this->alias) {
			$ret .= ' AS ' . $this->alias;
		}
		return $ret;
	}
}
