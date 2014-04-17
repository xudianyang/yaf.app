<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Db\Sql\Predicate;

class Predicate extends PredicateSet
{
	protected $unnest = null;
	protected $nextPredicateCombineOperator = null;

	/**
	 * Begin nesting predicates
	 *
	 * @return Predicate
	 */
	public function nest()
	{
		$predicateSet = new Predicate();
		$predicateSet->setUnnest($this);
		$this->addPredicate($predicateSet, ($this->nextPredicateCombineOperator) ? : $this->defaultCombination);
		$this->nextPredicateCombineOperator = null;
		return $predicateSet;
	}

	/**
	 * Indicate what predicate will be unnested
	 *
	 * @param  Predicate $predicate
	 * @return void
	 */
	public function setUnnest(Predicate $predicate)
	{
		$this->unnest = $predicate;
	}

	/**
	 * Indicate end of nested predicate
	 *
	 * @return Predicate
	 * @throws \RuntimeException
	 */
	public function unnest()
	{
		if ($this->unnest == null) {
			throw new \RuntimeException('Not nested');
		}
		$unnset = $this->unnest;
		$this->unnest = null;
		return $unnset;
	}

	/**
	 * Create "Equal To" predicate
	 * Utilizes Operator predicate
	 *
	 * @param  int|float|bool|string $left
	 * @param  int|float|bool|string $right
	 * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER
	 * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE
	 * @return Predicate
	 */
	public function eq($left, $right, $leftType = self::TYPE_IDENTIFIER, $rightType = self::TYPE_VALUE)
	{
		$this->addPredicate(
			new Operator($left, $right, Operator::OP_EQ, $leftType, $rightType),
			($this->nextPredicateCombineOperator) ? : $this->defaultCombination
		);
		$this->nextPredicateCombineOperator = null;

		return $this;
	}

	/**
	 * Create "Not Equal To" predicate
	 * Utilizes Operator predicate
	 *
	 * @param  int|float|bool|string $left
	 * @param  int|float|bool|string $right
	 * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER
	 * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE
	 * @return Predicate
	 */
	public function ne($left, $right, $leftType = self::TYPE_IDENTIFIER, $rightType = self::TYPE_VALUE)
	{
		$this->addPredicate(
			new Operator($left, $right, Operator::OP_NE, $leftType, $rightType),
			($this->nextPredicateCombineOperator) ? : $this->defaultCombination
		);
		$this->nextPredicateCombineOperator = null;

		return $this;
	}

	/**
	 * Create "Less Than" predicate
	 * Utilizes Operator predicate
	 *
	 * @param  int|float|bool|string $left
	 * @param  int|float|bool|string $right
	 * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER
	 * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE
	 * @return Predicate
	 */
	public function lt($left, $right, $leftType = self::TYPE_IDENTIFIER, $rightType = self::TYPE_VALUE)
	{
		$this->addPredicate(
			new Operator($left, $right, Operator::OP_LT, $leftType, $rightType),
			($this->nextPredicateCombineOperator) ? : $this->defaultCombination
		);
		$this->nextPredicateCombineOperator = null;

		return $this;
	}

	/**
	 * Create "Greater Than" predicate
	 * Utilizes Operator predicate
	 *
	 * @param  int|float|bool|string $left
	 * @param  int|float|bool|string $right
	 * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER
	 * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE
	 * @return Predicate
	 */
	public function gt($left, $right, $leftType = self::TYPE_IDENTIFIER, $rightType = self::TYPE_VALUE)
	{
		$this->addPredicate(
			new Operator($left, $right, Operator::OP_GT, $leftType, $rightType),
			($this->nextPredicateCombineOperator) ? : $this->defaultCombination
		);
		$this->nextPredicateCombineOperator = null;

		return $this;
	}

	/**
	 * Create "Less Than Or Equal To" predicate
	 * Utilizes Operator predicate
	 *
	 * @param  int|float|bool|string $left
	 * @param  int|float|bool|string $right
	 * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER
	 * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE
	 * @return Predicate
	 */
	public function lte($left, $right, $leftType = self::TYPE_IDENTIFIER, $rightType = self::TYPE_VALUE)
	{
		$this->addPredicate(
			new Operator($left, $right, Operator::OP_LTE, $leftType, $rightType),
			($this->nextPredicateCombineOperator) ? : $this->defaultCombination
		);
		$this->nextPredicateCombineOperator = null;

		return $this;
	}

	/**
	 * Create "Greater Than Or Equal To" predicate
	 * Utilizes Operator predicate
	 *
	 * @param  int|float|bool|string $left
	 * @param  int|float|bool|string $right
	 * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER
	 * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE
	 * @return Predicate
	 */
	public function gte($left, $right, $leftType = self::TYPE_IDENTIFIER, $rightType = self::TYPE_VALUE)
	{
		$this->addPredicate(
			new Operator($left, $right, Operator::OP_GTE, $leftType, $rightType),
			($this->nextPredicateCombineOperator) ? : $this->defaultCombination
		);
		$this->nextPredicateCombineOperator = null;

		return $this;
	}

	/**
	 * Create "Like" predicate
	 * Utilizes Like predicate
	 *
	 * @param  string $identifier
	 * @param  string $like
	 * @return Predicate
	 */
	public function like($identifier, $like)
	{
		$this->addPredicate(
			new Like($identifier, $like),
			($this->nextPredicateCombineOperator) ? : $this->defaultCombination
		);
		$this->nextPredicateCombineOperator = null;

		return $this;
	}

	/**
	 * Create an expression, with parameter placeholders
	 *
	 * @param $expression
	 * @param $parameters
	 * @return $this
	 */
	public function expression($expression, $parameters)
	{
		$this->addPredicate(
			new Expression($expression, $parameters),
			($this->nextPredicateCombineOperator) ? : $this->defaultCombination
		);
		$this->nextPredicateCombineOperator = null;

		return $this;
	}

	/**
	 * Create "Literal" predicate
	 * Literal predicate, for parameters, use expression()
	 *
	 * @param  string $literal
	 * @return Predicate
	 */
	public function literal($literal)
	{
		// process deprecated parameters from previous literal($literal, $parameters = null) signature
		if (func_num_args() >= 2) {
			$parameters = func_get_arg(1);
			$predicate = new Expression($literal, $parameters);
		}

		// normal workflow for "Literals" here
		if (!isset($predicate)) {
			$predicate = new Literal($literal);
		}

		$this->addPredicate(
			$predicate,
			($this->nextPredicateCombineOperator) ? : $this->defaultCombination
		);
		$this->nextPredicateCombineOperator = null;

		return $this;
	}

	/**
	 * Create "IS NULL" predicate
	 * Utilizes IsNull predicate
	 *
	 * @param  string $identifier
	 * @return Predicate
	 */
	public function isNull($identifier)
	{
		$this->addPredicate(
			new IsNull($identifier),
			($this->nextPredicateCombineOperator) ? : $this->defaultCombination
		);
		$this->nextPredicateCombineOperator = null;

		return $this;
	}

	/**
	 * Create "IS NOT NULL" predicate
	 * Utilizes IsNotNull predicate
	 *
	 * @param  string $identifier
	 * @return Predicate
	 */
	public function isNotNull($identifier)
	{
		$this->addPredicate(
			new IsNotNull($identifier),
			($this->nextPredicateCombineOperator) ? : $this->defaultCombination
		);
		$this->nextPredicateCombineOperator = null;

		return $this;
	}

	/**
	 * Create "in" predicate
	 * Utilizes In predicate
	 *
	 * @param  string $identifier
	 * @param  array|\Db\Sql\Select $valueSet
	 * @return Predicate
	 */
	public function in($identifier, $valueSet = null)
	{
		$this->addPredicate(
			new In($identifier, $valueSet),
			($this->nextPredicateCombineOperator) ? : $this->defaultCombination
		);
		$this->nextPredicateCombineOperator = null;

		return $this;
	}

	/**
	 * Create "between" predicate
	 * Utilizes Between predicate
	 *
	 * @param  string $identifier
	 * @param  int|float|string $minValue
	 * @param  int|float|string $maxValue
	 * @return Predicate
	 */
	public function between($identifier, $minValue, $maxValue)
	{
		$this->addPredicate(
			new Between($identifier, $minValue, $maxValue),
			($this->nextPredicateCombineOperator) ? : $this->defaultCombination
		);
		$this->nextPredicateCombineOperator = null;

		return $this;
	}
}
