<?php
/**
 * Api.cloud
 *
 * @copyright Copyright (c) 2013 Beijing CmsTop Technology Co.,Ltd. (http://www.cmstop.com)
 */

namespace Db\Table;

interface TableInterface
{
	public function getTable();

	public function select($where = null);

	public function insert($set);

	public function update($set, $where = null);

	public function delete($where);
}
