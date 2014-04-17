<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */
namespace Db\Sql;

use Db\Adapter\Driver;
use Db\Adapter\Platform\PlatformInterface;

interface SqlInterface
{
	/**
	 * @param PlatformInterface $platform
	 * @return $this
	 */
	public function setPlatform(PlatformInterface $platform);

	/**
	 * @param Driver\DriverInterface $driver
	 * @return $this
	 */
	public function setDriver(Driver\DriverInterface $driver);

	/**
	 * @param PlatformInterface|null $platform
	 * @param Driver\DriverInterface|null $driver
	 * @return Driver\StatementInterface
	 */
	public function prepareStatement(PlatformInterface $platform = null, Driver\DriverInterface $driver = null);

	/**
	 * @param PlatformInterface $platform
	 * @return string
	 */
	public function getSqlString(PlatformInterface $platform = null);

	/**
	 * @param null|array|\Db\Adapter\Parameters $parameters
	 * @return Driver\ResultInterface
	 */
	public function execute($parameters = null);
}
