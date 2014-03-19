<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */
namespace Db\Adapter\Driver;

interface ConnectionInterface
{
	/**
	 * Get current schema
	 *
	 * @return string
	 */
	public function getCurrentSchema();

	/**
	 * Get resource
	 *
	 * @return mixed
	 */
	public function getResource();

	/**
	 * Connect
	 *
	 * @return $this
	 */
	public function connect();

	/**
	 * Is connected
	 *
	 * @return bool
	 */
	public function isConnected();

	/**
	 * Disconnect
	 *
	 * @return $this
	 */
	public function disconnect();

	/**
	 * Begin transaction
	 *
	 * @return $this
	 */
	public function beginTransaction();

	/**
	 * Commit
	 *
	 * @return $this
	 */
	public function commit();

	/**
	 * Rollback
	 *
	 * @return $this
	 */
	public function rollback();

	/**
	 * Execute
	 *
	 * @param  string $sql
	 * @return ResultInterface
	 */
	public function execute($sql);

	/**
	 * Prepare
	 *
	 * @param string $sql
	 * @return StatementInterface
	 */
	public function prepare($sql);

	/**
	 * Get last generated id
	 *
	 * @param  null $name Ignored
	 * @return integer
	 */
	public function getLastGeneratedValue($name = null);
}
