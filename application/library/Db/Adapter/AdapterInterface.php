<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Db\Adapter;

interface AdapterInterface
{
	/**
	 * @return Driver\DriverInterface
	 */
	public function getDriver();

	/**
	 * @return Platform\PlatformInterface
	 */
	public function getPlatform();

	/**
	 * @return Metadata\MetadataInterface
	 */
	public function getMetadata();
}
