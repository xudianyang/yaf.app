<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Db\Adapter\Metadata;

interface MetadataInterface
{
	/**
	 * @param string $table
	 * @param string $schema
	 * @return array
	 */
	public function getColumns($table, $schema = null);

	/**
	 * @param string $table
	 * @param string $schema
	 * @return array
	 */
	public function getPrimarys($table, $schema = null);
}
