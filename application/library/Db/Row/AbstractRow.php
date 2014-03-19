<?php
/**
 * Api.cloud
 *
 * @copyright Copyright (c) 2013 Beijing CmsTop Technology Co.,Ltd. (http://www.cmstop.com)
 */

namespace Db\Row;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Db\Table\AbstractTable;
use Traversable;
use Db\Sql\Sql;
use Validator\ValidatorChain;

abstract class AbstractRow implements ArrayAccess, IteratorAggregate, Countable, RowInterface
{
	const ON_CREATE = 'onCreate';

	const ON_UPDATE = 'onUpdate';

	/**
	 * @var bool
	 */
	protected $isInitialized = false;

	/**
	 * @var array
	 */
	protected $columns;

	/**
	 * @var array
	 */
	protected $primaryKey;

	/**
	 * @var array
	 */
	protected $primaryKeyData;

	/**
	 * @var array
	 */
	protected $cleanData = array();

	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * @var Sql
	 */
	protected $sql;

	/**
	 * @var ValidatorChain[]
	 */
	protected $validatorChains = array();

	/** @var array */
	protected $invalidColumns = array();

	/**
	 * @var array
	 */
	protected $autofills = array(
		self::ON_CREATE => array(),
		self::ON_UPDATE => array()
	);

	/**
	 * initialize()
	 */
	public function initialize()
	{
		if ($this->isInitialized) {
			return;
		}

		if (empty($this->columns)) {
			throw new Exception\RuntimeException('This row object must be setup columns.');
		} elseif (array_keys($this->columns) == range(0, count($this->columns) - 1)) {
			$this->columns = array_flip($this->columns);
		}

		if ($this->primaryKey == null) {
			throw new Exception\RuntimeException('This row object does not have a primary key column set.');
		} elseif (is_string($this->primaryKey)) {
			$this->primaryKey = (array)$this->primaryKey;
		}

		if (!$this->sql instanceof Sql) {
			throw new Exception\RuntimeException('This row object does not have a Sql object set.');
		}

		$this->isInitialized = true;
	}

	/**
	 * @param AbstractTable $table
	 * @return $this
	 */
	public function setupWithTable(AbstractTable $table)
	{
		$this->sql = $table->getSql();
		$adapter = $this->sql->getAdapter();
		$table = $table->getTable();
		$metadata = $adapter->getMetadata();
		$this->columns = array_keys($metadata->getColumns($table->getTable(), $table->getSchema()));
		$this->primaryKey = $metadata->getPrimarys($table->getTable(), $table->getSchema());

		return $this;
	}

	/**
	 * @param string $column
	 * @return ValidatorChain
	 * @throws Exception\InvalidArgumentException
	 */
	public function getValidatorChain($column)
	{
		if (!isset($this->validatorChains[$column])) {
			$this->validatorChains[$column] = new ValidatorChain;
		}
		return $this->validatorChains[$column];
	}

	/**
	 * Returns the columns failure on last validation
	 *
	 * @return array
	 */
	public function getInvalidColumns()
	{
		return $this->invalidColumns;
	}

	/**
	 * add validator
	 *
	 * @param string $column
	 * @param string|\Validator\ValidatorInterface|callable $validator
	 * @param null|string $message
	 * @return $this
	 */
	public function addValidator($column, $validator, $message = NULL)
	{
		$this->getValidatorChain($column)->addValidator($validator, $message);
		return $this;
	}

	/**
	 * Valid modified data
	 *
	 * @param null|bool $breakChainOnFailure
	 * @return bool
	 */
	public function isValid($breakChainOnFailure = null)
	{
		$data = $this->getModified();
		$columns = $data;
		if (!$this->rowExistsInDatabase()) {
			$columns = $this->columns;
		}

		/** @var ValidatorChain[] $chains */
		$chains = array_intersect_key($this->validatorChains, $columns);
		if (empty($chains)) {
			return true;
		}

		$this->invalidColumns = array();
		foreach ($chains as $column => $chain) {
			if (!$chain->isValid(isset($this->data[$column]) ? $this->data[$column] : null, $breakChainOnFailure))
			{
				$this->invalidColumns[] = $column;
				if ($breakChainOnFailure === true) {
					return false;
				}
			}
		}

		return empty($this->invalidColumns);
	}

	/**
	 * @param string $column
	 * @param mixed $autofill
	 * @param string $on
	 * @return $this
	 * @throws Exception\InvalidArgumentException
	 */
	public function addAutofill($column, $autofill, $on = self::ON_CREATE)
	{
		if (!isset($this->autofills[$on])) {
			throw new Exception\InvalidArgumentException(sprintf(
				'$on must be ON_CREATE or ON_UPDATE; received "%s"', $on
			));
		}
		$this->autofills[$on][$column] = $autofill;
		return $this;
	}

	/**
	 * Populate Data
	 *
	 * @param  array|object $rowData
	 * @param  bool $rowExistsInDatabase
	 * @return $this
	 */
	public function populate($rowData, $rowExistsInDatabase = false)
	{
		$this->initialize();

		$data = $this->pickupData($rowData);
		if ($rowExistsInDatabase) {
			$this->data = $data;
			$this->cleanData = $data;
			$this->processPrimaryKeyData();
		} else {
			$this->cleanData = array();
			$this->primaryKeyData = null;
			$this->data = array();
			foreach ($data as $column => $value) {
				$this->set($column, $value);
			}
		}

		return $this;
	}

	protected function pickupData($data)
	{
		if (is_object($data)) {
			if (method_exists($data, 'toArray')) {
				$data = $data->toArray();
			} elseif ($data instanceof Traversable) {
				$temp = array();
				foreach ($data as $key => $val) {
					$temp[$key] = $val;
				}
				$data = $temp;
			} else {
				$data = (array) $data;
			}
		}
		if (!is_array($data)) {
			throw new Exception\InvalidArgumentException(sprintf(
				'%s: expects an array, or Traversable argument; received "%s"',
				__METHOD__, gettype($data)
			));
		}
		return array_intersect_key($data, $this->columns);
	}

	/**
	 * @param mixed $array
	 * @return $this
	 */
	public function exchangeArray($array)
	{
		return $this->populate($array, true);
	}

	/**
	 * Merge the new data
	 *
	 * @param array|Traversable|object $data
	 * @return $this
	 */
	public function mergeData($data)
	{
		$this->initialize();
		$data = $this->pickupData($data);
		foreach ($data as $column => $value) {
			$this->set($column, $value);
		}
		return $this;
	}

    /**
     * Save
     *
     * @param bool $ignoreValidation
     * @return int
     * @throws Exception\ValidateException
     */
    public function save($ignoreValidation = false)
	{
		$this->initialize();

		$on = $this->rowExistsInDatabase() ? self::ON_UPDATE : self::ON_CREATE;

		// autofill empty sets
		if (!empty($this->autofills[$on])) {
			foreach ($this->autofills[$on] as $column => $autofill) {
				if (!isset($this->columns[$column]) || $this->isModified($column) || !$this->isNull($column))
				{
					continue;
				}
				$this->set($column, is_callable($autofill) ? $autofill($column, $this) : $autofill);
			}
		}

		// validate data
		if (!$ignoreValidation && !$this->isValid(true)) {
			$invalidColumn = reset($this->invalidColumns);
			throw new Exception\ValidateException($this->getValidatorChain($invalidColumn)->getFirstMessage());
		}

		// get modified data
		$data = $this->getModified();

		if (empty($data)) {
			return 0;
		}

		if ($this->rowExistsInDatabase()) {

			// UPDATE

			$where = $this->processPrimaryKeyWhere();

			$statement = $this->sql->prepareStatement($this->sql->update()->set($data)->where($where));
			$result = $statement->execute();
			$rowsAffected = $result->getAffectedRows();
			unset($statement, $result); // cleanup

		} else {

			// INSERT

			$insert = $this->sql->insert();
			$insert->values($data);

			$statement = $this->sql->prepareStatement($insert);

			$result = $statement->execute();
			if (($primaryKeyValue = $result->getGeneratedValue()) && count($this->primaryKey) == 1) {
				$this->primaryKeyData = array($this->primaryKey[0] => $primaryKeyValue);
			} else {
				// make primary key data available so that $where can be complete
				$this->processPrimaryKeyData();
			}
			$rowsAffected = $result->getAffectedRows();
			unset($statement, $result); // cleanup
		}

		// refresh data
		$this->refresh();

		// return rows affected
		return $rowsAffected;
	}

	/**
	 * Refresh data from database
	 *
	 * @return $this
	 */
	public function refresh()
	{
		$this->initialize();

		if (!$this->rowExistsInDatabase()) {
			throw new Exception\RuntimeException('Cannot refresh the data not exists in database.');
		}
		$where = $this->processPrimaryKeyWhere();

		$statement = $this->sql->prepareStatement($this->sql->select()->where($where));
		$result = $statement->execute();
		$rowData = $result->current();
		unset($statement, $result); // cleanup

		// make sure data and original data are in sync after save
		$this->populate($rowData, true);
		return $this;
	}

	/**
	 * Get modified data
	 *
	 * @return array
	 */
	public function getModified()
	{
		if (empty($this->cleanData)) {
			return $this->data;
		}
		$data = $this->data;
		foreach ($data as $key => $val) {
			if (array_key_exists($key, $this->cleanData) && $val === $this->cleanData[$key]) {
				unset($data[$key]);
			}
		}
		return $data;
	}

	/**
	 * Check the column if modified
	 *
	 * @param string $column
	 * @return bool
	 */
	public function isModified($column)
	{
		if (empty($this->cleanData) || !array_key_exists($column, $this->cleanData)) {
			return array_key_exists($column, $this->data);
		}
		return $this->cleanData[$column] !== $this->data[$column];
	}

	/**
	 * Check column data is null or empty string
	 *
	 * @param string $column
	 * @return bool
	 */
	public function isNull($column)
	{
		return !isset($this->data[$column]) || !strlen($this->data[$column]);
	}

	/**
	 * Delete
	 *
	 * @return int
	 */
	public function delete()
	{
		$this->initialize();

		if (!$this->rowExistsInDatabase()) {
			throw new Exception\RuntimeException('Cannot refresh the data not exists in database.');
		}
		$where = $this->processPrimaryKeyWhere();

		$statement = $this->sql->prepareStatement($this->sql->delete()->where($where));
		$result = $statement->execute();

		$rowsAffected = $result->getAffectedRows();
		if ($rowsAffected == 1) {
			// detach from database
			$this->primaryKeyData = null;
			$this->data = array();
			$this->cleanData = array();
		}

		return $rowsAffected;
	}

	/**
	 * Get a column value
	 *
	 * @param string $column
	 * @return mixed
	 * @throws Exception\InvalidArgumentException
	 */
	public function get($column)
	{
		$this->initialize();
		if (array_key_exists($column, $this->columns)) {
			$accessor = '__get' . str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $column)));
			return method_exists($this, $accessor) ? $this->{$accessor}() : $this->data[$column];
		}
		throw new Exception\InvalidArgumentException('Not a valid column in this row: ' . $column);
	}

	/**
	 * Set the column value
	 *
	 * @param string $column
	 * @param mixed $value
	 * @throws Exception\InvalidArgumentException
	 */
	public function set($column, $value)
	{
		$this->initialize();
		if (array_key_exists($column, $this->columns)) {
			$mutator = '__set' . str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $column)));
			if (method_exists($this, $mutator)) {
				$this->{$mutator}($value);
			} else {
				$this->data[$column] = $value;
			}
			return;
		}
		throw new Exception\InvalidArgumentException('Not a valid column in this row: ' . $column);
	}

	/**
	 * Exists the column
	 *
	 * @param string $column
	 * @return bool
	 */
	public function exists($column)
	{
		$this->initialize();
		return array_key_exists($column, $this->columns);
	}

	/**
	 * Offset Exists
	 *
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return $this->exists($offset);
	}

	/**
	 * Offset get
	 *
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	/**
	 * Offset set
	 *
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}

	/**
	 * Offset unset
	 *
	 * @param string $offset
	 */
	public function offsetUnset($offset)
	{
		$this->initialize();
		if (array_key_exists($offset, $this->columns)) {
			$this->data[$offset] = null;
		}
	}

	/**
	 * @return int
	 */
	public function count()
	{
		return count($this->data);
	}

	/**
	 * To array
	 *
	 * @param bool $filter with accessor
	 * @return array
	 */
	public function toArray($filter = false)
	{
		$data = $this->data;
		if ($filter) {
			foreach ($data as $column => &$value) {
				$value = $this->get($column);
			}
		}
		return $data;
	}

	/**
	 * __get
	 *
	 * @param  string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->get($name);
	}

	/**
	 * __set
	 *
	 * @param  string $name
	 * @param  mixed $value
	 * @return void
	 */
	public function __set($name, $value)
	{
		$this->set($name, $value);
	}

	/**
	 * __isset
	 *
	 * @param  string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return $this->exists($name);
	}

	/**
	 * __unset
	 *
	 * @param  string $name
	 * @return void
	 */
	public function __unset($name)
	{
		$this->offsetUnset($name);
	}

	/**
	 * Required by interface IteratorAggregate, use for foreach
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->data);
	}

	/**
	 * @return bool
	 */
	public function rowExistsInDatabase()
	{
		return ($this->primaryKeyData !== null);
	}

	/**
	 * @throws Exception\RuntimeException
	 */
	protected function processPrimaryKeyData()
	{
		$this->primaryKeyData = array();
		foreach ($this->primaryKey as $column) {
			if (!isset($this->data[$column])) {
				throw new Exception\RuntimeException(
					'While processing primary key data, a known key ' . $column . ' was not found in the data array');
			}
			$this->primaryKeyData[$column] = $this->data[$column];
		}
	}

	protected function processPrimaryKeyWhere()
	{
		$where = array();
		// primary key is always an array even if its a single column
		foreach ($this->primaryKey as $column) {
			$where[$column] = $this->primaryKeyData[$column];
		}
		return $where;
	}
}