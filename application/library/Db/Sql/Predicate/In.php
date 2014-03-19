<?php
/**
 * Api.cloud
 *
 * @copyright Copyright (c) 2013 Beijing CmsTop Technology Co.,Ltd. (http://www.cmstop.com)
 */

namespace Db\Sql\Predicate;

use Db\Sql\Select;
use Db\Sql\Exception;

class In implements PredicateInterface
{
	protected $identifier;
	protected $valueSet;

	/**
	 * Constructor
	 *
	 * @param  null|string $identifier
	 * @param  array|Select $valueSet
	 */
	public function __construct($identifier, $valueSet)
	{
		$this->identifier = $identifier;
		if (!is_array($valueSet) && !$valueSet instanceof Select) {
			throw new Exception\InvalidArgumentException(
				'$valueSet must be either an array or a Select object, ' . gettype($valueSet) . ' given'
			);
		}
		$this->valueSet = $valueSet;
	}


	/**
	 * Return array of parts for where statement
	 *
	 * @return array
	 */
	public function getExpressionData()
	{
		$values = $this->valueSet;
		if ($values instanceof Select) {
			$specification = '%s IN %s';
			$types = array(self::TYPE_VALUE);
			$values = array($values);
		} else {
			$specification = '%s IN (' . implode(', ', array_fill(0, count($values), '%s')) . ')';
			$types = array_fill(0, count($values), self::TYPE_VALUE);
		}

		array_unshift($values, $this->identifier);
		array_unshift($types, self::TYPE_IDENTIFIER);

		return array(
			array(
				$specification,
				$values,
				$types,
			)
		);
	}

}
