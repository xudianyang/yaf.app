<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
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
