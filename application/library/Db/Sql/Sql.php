<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Db\Sql;

use Db\Adapter\AdapterInterface;
use Db\Adapter\AdapterPool;
use Db\Adapter\Driver\StatementInterface;

class Sql
{
	/** @var AdapterInterface */
	protected $adapter;

	/** @var TableIdentifier */
	protected $table;

	/** @var SqlInterface */
	protected $lastSql;

	/**
	 * @param AdapterInterface $adapter
	 * @param string|TableIdentifier $table
	 */
	public function __construct(AdapterInterface $adapter = null, $table = null)
	{
		if (!$adapter && !($adapter = AdapterPool::get())) {
			throw new Exception\RuntimeException('A adapter must be suplied');
		}

		$this->adapter = $adapter;
		if ($table) {
			$this->setTable($table);
		}
	}

	/**
	 * @return AdapterInterface
	 */
	public function getAdapter()
	{
		return $this->adapter;
	}

	public function hasTable()
	{
		return ($this->table != null);
	}

	/**
	 * @param string|TableIdentifier $table
	 * @return Sql
	 * @throws Exception\InvalidArgumentException
	 */
	public function setTable($table)
	{
		if (!is_string($table) && !$table instanceof TableIdentifier) {
			throw new Exception\InvalidArgumentException('Table must be a string or instance of TableIdentifier.');
		}
		$this->table = $table instanceof TableIdentifier ? $table : new TableIdentifier($table);
		return $this;
	}

	/**
	 * @return TableIdentifier
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * @param string|array|TableIdentifier $table
	 * @return Select
	 * @throws Exception\InvalidArgumentException
	 */
	public function select($table = null)
	{
		if ($this->table !== null && $table !== null) {
			throw new Exception\InvalidArgumentException(
				'This Sql object is intended to work with only the table provided at construction time.'
			);
		}
		$this->lastSql = new Select(($table) ? : $this->table);
		$this->lastSql->setPlatform($this->adapter->getPlatform());
		$this->lastSql->setDriver($this->adapter->getDriver());
		return $this->lastSql;
	}

	/**
	 * @param string|TableIdentifier $table
	 * @return Insert
	 * @throws Exception\InvalidArgumentException
	 */
	public function insert($table = null)
	{
		if ($this->table !== null && $table !== null) {
			throw new Exception\InvalidArgumentException(
				'This Sql object is intended to work with only the table provided at construction time.'
			);
		}
		$this->lastSql = new Insert(($table) ? : $this->table);
		$this->lastSql->setPlatform($this->adapter->getPlatform());
		$this->lastSql->setDriver($this->adapter->getDriver());
		return $this->lastSql;
	}

	/**
	 * @param string|TableIdentifier $table
	 * @return Update
	 * @throws Exception\InvalidArgumentException
	 */
	public function update($table = null)
	{
		if ($this->table !== null && $table !== null) {
			throw new Exception\InvalidArgumentException(
				'This Sql object is intended to work with only the table provided at construction time.'
			);
		}
		$this->lastSql = new Update(($table) ? : $this->table);
		$this->lastSql->setPlatform($this->adapter->getPlatform());
		$this->lastSql->setDriver($this->adapter->getDriver());
		return $this->lastSql;
	}

	/**
	 * @param string|TableIdentifier $table
	 * @return Delete
	 * @throws Exception\InvalidArgumentException
	 */
	public function delete($table = null)
	{
		if ($this->table !== null && $table !== null) {
			throw new Exception\InvalidArgumentException(
				'This Sql object is intended to work with only the table provided at construction time.'
			);
		}
		$this->lastSql = new Delete(($table) ? : $this->table);
		$this->lastSql->setPlatform($this->adapter->getPlatform());
		$this->lastSql->setDriver($this->adapter->getDriver());
		return $this->lastSql;
	}

	/**
	 * @param SqlInterface|null $sql
	 * @return StatementInterface
	 */
	public function prepareStatement(SqlInterface $sql = null)
	{
		$sql = $sql ?: $this->lastSql;
		if (!$sql) {
			throw new Exception\InvalidArgumentException('Sql sequence is not supplied.');
		}
		return $sql->prepareStatement($this->adapter->getPlatform(), $this->adapter->getDriver());
	}

	/**
	 * @param SqlInterface|null $sql
	 * @return string
	 */
	public function getSqlString(SqlInterface $sql = null)
	{
		$sql = $sql ?: $this->lastSql;
		if (!$sql) {
			throw new Exception\InvalidArgumentException('Sql sequence is not supplied.');
		}
		return $sql->getSqlString($this->adapter->getPlatform());
	}
}
