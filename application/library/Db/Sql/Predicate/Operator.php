<?php
/**
 * Api.cloud
 *
 * @copyright Copyright (c) 2013 Beijing CmsTop Technology Co.,Ltd. (http://www.cmstop.com)
 */

namespace Db\Sql\Predicate;

use Db\Sql\Exception;

class Operator implements PredicateInterface
{
	const OP_EQ = '=';
	const OP_NE = '!=';
	const OP_LT = '<';
	const OP_LTE = '<=';
	const OP_GT = '>';
	const OP_GTE = '>=';

	protected $left = null;
	protected $leftType = self::TYPE_IDENTIFIER;
	protected $operator = self::OP_EQ;
	protected $right = null;
	protected $rightType = self::TYPE_VALUE;

	/**
	 * Constructor
	 *
	 * @param  int|float|bool|string $left
	 * @param  string $operator
	 * @param  int|float|bool|string $right
	 * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER
	 * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE
	 */
	public function __construct($left, $right, $operator = self::OP_EQ,
		$leftType = self::TYPE_IDENTIFIER, $rightType = self::TYPE_VALUE)
	{
		$this->left = $left;
		$this->right = $right;
		$this->operator = $operator;
		$this->leftType = $leftType;
		$this->rightType = $rightType;
	}

	/**
	 * Get predicate parts for where statement
	 *
	 * @return array
	 */
	public function getExpressionData()
	{
		return array(
			array(
				'%s ' . $this->operator . ' %s',
				array($this->left, $this->right),
				array($this->leftType, $this->rightType)
			)
		);
	}

}
