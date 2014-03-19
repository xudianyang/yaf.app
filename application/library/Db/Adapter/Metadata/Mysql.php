<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Db\Adapter\Metadata;

class Mysql extends AbstractMetadata
{
	/**
	 * @param string $table
	 * @param string $schema
	 * @return array
	 */
	protected function load($table, $schema)
	{
		$p = $this->adapter->getPlatform();

		if ($schema != self::DEFAULT_SCHEMA) {
			$qtable = $p->quoteIdentifierChain(array($schema, $table));
		} else {
			$qtable = $p->quoteValue($table);
		}

		$results = $this->adapter->getDriver()->getConnection()->execute("DESCRIBE $qtable");


		$field = 'Field';
		$type = 'Type';
		$null = 'Null';
		$key = 'Key';
		$default = 'Default';
		$extra = 'Extra';

		$columns = array();
		$i = 1;
		$p = 1;
		foreach ($results as $row) {
			list($length, $unsigned, $primary, $primaryPos, $identity)
				= array(null, null, false, null, false);
			if (preg_match('/unsigned/', $row[$type])) {
				$unsigned = true;
			}
			if (preg_match('/^((?:var)?char)\((\d+)\)/', $row[$type], $matches)) {
				$row[$type] = $matches[1];
				$length = $matches[2];
			} else {
				if (preg_match('/^(decimal|float)/', $row[$type], $matches)) {
					$row[$type] = $matches[1];
				} else {
					if (preg_match('/^((?:big|medium|small|tiny)?int)\(\d+\)/',
						$row[$type], $matches)
					) {
						$row[$type] = $matches[1];
					}
				}
			}
			if (strtoupper($row[$key]) == 'PRI') {
				$primary = true;
				$primaryPos = $p;
				if ($row[$extra] == 'auto_increment') {
					$identity = true;
				} else {
					$identity = false;
				}
				++$p;
			}
			$columnName = $row[$field];
			$columns[$columnName] = array(
				'COLUMN_NAME' => $columnName,
				'COLUMN_POS'  => $i,
				'DATA_TYPE'   => $row[$type],
				'DEFAULT'     => $row[$default],
				'NULLABLE'    => (bool)($row[$null] == 'YES'),
				'LENGTH'      => $length,
				'UNSIGNED'    => $unsigned,
				'PRIMARY'     => $primary,
				'PRIMARY_POS' => $primaryPos,
				'IDENTITY'    => $identity
			);
			++$i;
		}

		return $columns;
	}
}
