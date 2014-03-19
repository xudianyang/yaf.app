<?php
/**
 * Api.cloud
 *
 * @copyright Copyright (c) 2013 Beijing CmsTop Technology Co.,Ltd. (http://www.cmstop.com)
 */

namespace Db\Table;

use Db\Adapter\AdapterInterface;
use Db\Row\AbstractRow;
use Db\Row\Row;
use Db\Sql\Delete;
use Db\Sql\Insert;
use Db\Sql\Select;
use Db\Sql\Sql;
use Db\Sql\TableIdentifier;
use Db\Sql\Update;
use Db\Sql\Where;

abstract class AbstractTable implements TableInterface
{
	/**
	 * @var bool
	 */
	protected $isInitialized = false;

	/**
	 * @var AdapterInterface
	 */
	protected $adapter = null;

	/**
	 * @var TableIdentifier
	 */
	protected $table = null;

	/**
	 * @var Sql
	 */
	protected $sql;

	/**
	 * @var AbstractRow
	 */
	protected $rowPrototype;

	/**
	 * @var integer
	 */
	protected $lastInsertValue = null;

	/**
	 * @return bool
	 */
	public function isInitialized()
	{
		return $this->isInitialized;
	}

	/**
	 * Initialize
	 *
	 * @throws Exception\RuntimeException
	 * @return null
	 */
	public function initialize()
	{
		if ($this->isInitialized) {
			return;
		}

		if (!$this->adapter instanceof AdapterInterface) {
			throw new Exception\RuntimeException('This table does not have an Adapter set.');
		}

		if (!$this->table instanceof TableIdentifier) {
			throw new Exception\RuntimeException('This table does not have a valid table set.');
		}

		if (!$this->sql instanceof Sql) {
			$this->sql = new Sql($this->adapter, $this->table);
		}

		if ((string)$this->sql->getTable() != (string)$this->table) {
			throw new Exception\RuntimeException(
				'The table inside the provided Sql object must match the table of this Table');
		}
		if (!$this->rowPrototype instanceof AbstractRow) {
			$this->rowPrototype = new Row;
		}
		$this->rowPrototype->setupWithTable($this);

		$this->isInitialized = true;
	}

	/**
	 * @param TableIdentifier $table
	 * @return $this
	 */
	public function setTable(TableIdentifier $table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * Get table name
	 *
	 * @return TableIdentifier
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * @param AdapterInterface $adapter
	 * @return $this
	 */
	public function setAdapter(AdapterInterface $adapter)
	{
		$this->adapter = $adapter;
		return $this;
	}

	/**
	 * Get adapter
	 *
	 * @return AdapterInterface
	 */
	public function getAdapter()
	{
		return $this->adapter;
	}

	/**
	 * @param Sql $sql
	 * @return $this
	 */
	public function setSql(Sql $sql)
	{
		$this->sql = $sql;
		if ($this->getTable() && (string)$this->sql->getTable() != (string)$this->getTable()) {
			throw new Exception\RuntimeException(
				'The table inside the provided Sql object must match the table of this Table');
		}
		return $this;
	}

	/**
	 * @return Sql
	 */
	public function getSql()
	{
		return $this->sql;
	}

	/**
	 * Select
	 *
	 * @param Where|\Closure|string|array $where
	 * @param Pagination $pagination
	 * @return array
	 */
	public function select($where = null, Pagination $pagination = null)
	{
		if (!$this->isInitialized) {
			$this->initialize();
		}

		/** @var $select Select */
		$select = $this->sql->select();

		if ($where instanceof \Closure) {
			$where($select);
		} elseif ($where !== null) {
			$select->where($where);
		}

		return $this->selectWith($select, $pagination);
	}

	/**
	 * @param Select $select
	 * @param Pagination $pagination
	 * @return array
	 */
	public function selectWith(Select $select, Pagination $pagination = null)
	{
		if (!$this->isInitialized) {
			$this->initialize();
		}
		return $this->executeSelect($select, $pagination);
	}

	/**
	 * @param Select $select
	 * @param Pagination $pagination
	 * @throws Exception\RuntimeException
	 * @return array
	 */
	protected function executeSelect(Select $select, Pagination $pagination = null)
	{
		$selectState = $select->getRawState();
		if ((string)$selectState['table'] != (string)$this->table) {
			throw new Exception\RuntimeException(
				'The table name of the provided select object must match that of the table');
		}

		if ($pagination) {
			$counter = clone $select;
			$counter->reset(Select::COLUMNS)->reset(Select::ORDER)
				->reset(Select::LIMIT)->reset(Select::OFFSET);
			$counter->columns('COUNT(*) as total');
			$row = $this->sql->prepareStatement($counter)->execute()->current();
			$pagination->setRecordCount($row['total']);

			$size = $pagination->getPageSize();
			$currentPage = $pagination->getCurrentPage();
			$select->limit($size)->offset(($currentPage - 1) * $size);
		}

		// prepare and execute
		$statement = $this->sql->prepareStatement($select);
		$rowset = array();
		foreach ($statement->execute() as $row) {
			$rowset[] = $row;
		}
		return $rowset;
	}

	/**
	 * Insert
	 *
	 * @param  array $set
	 * @return int
	 */
	public function insert($set)
	{
		if (!$this->isInitialized) {
			$this->initialize();
		}
		$insert = $this->sql->insert();
		$insert->values($set);
		return $this->executeInsert($insert);
	}

	/**
	 * @param Insert $insert
	 * @return mixed
	 */
	public function insertWith(Insert $insert)
	{
		if (!$this->isInitialized) {
			$this->initialize();
		}
		return $this->executeInsert($insert);
	}

	/**
	 * @param Insert $insert
	 * @return mixed
	 * @throws Exception\RuntimeException
	 */
	protected function executeInsert(Insert $insert)
	{
		$insertState = $insert->getRawState();
		if ((string)$insertState['table'] != (string)$this->table) {
			throw new Exception\RuntimeException(
				'The table name of the provided Insert object must match that of the table');
		}

		$statement = $this->sql->prepareStatement($insert);
		$result = $statement->execute();
		$this->lastInsertValue = $this->sql->getAdapter()->getDriver()->getConnection()->getLastGeneratedValue();

		return $result->getAffectedRows();
	}

	/**
	 * Update
	 *
	 * @param  array $set
	 * @param  string|array|\Closure $where
	 * @return int
	 */
	public function update($set, $where = null)
	{
		if (!$this->isInitialized) {
			$this->initialize();
		}
		$sql = $this->sql;
		$update = $sql->update();
		$update->set($set);
		if ($where !== null) {
			$update->where($where);
		}
		return $this->executeUpdate($update);
	}

	/**
	 * @param Update $update
	 * @return mixed
	 */
	public function updateWith(Update $update)
	{
		if (!$this->isInitialized) {
			$this->initialize();
		}
		return $this->executeUpdate($update);
	}

	/**
	 * @param Update $update
	 * @return mixed
	 * @throws Exception\RuntimeException
	 */
	protected function executeUpdate(Update $update)
	{
		$updateState = $update->getRawState();
		if ((string)$updateState['table'] != (string)$this->table) {
			throw new Exception\RuntimeException(
				'The table name of the provided Update object must match that of the table');
		}

		$statement = $this->sql->prepareStatement($update);
		$result = $statement->execute();

		return $result->getAffectedRows();
	}

	/**
	 * Delete
	 *
	 * @param  Where|\Closure|string|array $where
	 * @return int
	 */
	public function delete($where)
	{
		if (!$this->isInitialized) {
			$this->initialize();
		}
		$delete = $this->sql->delete();
		if ($where instanceof \Closure) {
			$where($delete);
		} else {
			$delete->where($where);
		}
		return $this->executeDelete($delete);
	}

	/**
	 * @param Delete $delete
	 * @return mixed
	 */
	public function deleteWith(Delete $delete)
	{
		$this->initialize();
		return $this->executeDelete($delete);
	}

	/**
	 * @param Delete $delete
	 * @return mixed
	 * @throws Exception\RuntimeException
	 */
	protected function executeDelete(Delete $delete)
	{
		$deleteState = $delete->getRawState();
		if ((string)$deleteState['table'] != (string)$this->table) {
			throw new Exception\RuntimeException(
				'The table name of the provided Update object must match that of the table');
		}

		$statement = $this->sql->prepareStatement($delete);
		$result = $statement->execute();

		return $result->getAffectedRows();
	}

	/**
	 * prepare a row for create
	 *
	 * @param null|array|object $data
	 * @return AbstractRow
	 */
	public function create($data = null)
	{
		$this->initialize();

		$row = clone $this->rowPrototype;
		$row->populate($data ?: array(), false);

		return $row;
	}

	/**
	 * get one row
	 *
	 * @param Where|\Closure|string|array $where
	 * @param bool $returnArray
	 * @throws Exception\InvalidArgumentException
	 * @return null|array|AbstractRow
	 */
	public function get($where = null, $returnArray = false)
	{
		$this->initialize();

		$select = $this->sql->select();

		if ($where instanceof \Closure) {
			$where($select);
		} elseif ($where !== null) {
			$select->where($where);
		}

		// prepare and execute
		$statement = $this->sql->prepareStatement($select);
		$result = $statement->execute();

		$rowData = $result->current();

		if (false === $rowData) {
			return null;
		}

		if ($returnArray) {
			return $rowData;
		}

		$row = clone $this->rowPrototype;
		$row->exchangeArray($rowData);

		return $row;
	}

	/**
	 * Get last insert value
	 *
	 * @return integer
	 */
	public function getLastInsertValue()
	{
		return $this->lastInsertValue;
	}

	/**
	 * __clone
	 */
	public function __clone()
	{
		$this->rowProrotype = isset($this->rowProrotype) ? clone $this->rowProrotype : null;
		$this->sql = clone $this->sql;
		if (is_object($this->table)) {
			$this->table = clone $this->table;
		}
	}
}
