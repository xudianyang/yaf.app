<?php
/**
 * Api.cloud
 *
 * @copyright Copyright (c) 2013 Beijing CmsTop Technology Co.,Ltd. (http://www.cmstop.com)
 */

namespace Db\Sql\Predicate;

class Between implements PredicateInterface
{
	protected $specification = '%1$s BETWEEN %2$s AND %3$s';
	protected $identifier = null;
	protected $minValue = null;
	protected $maxValue = null;

	/**
	 * Constructor
	 *
	 * @param  string $identifier
	 * @param  int|float|string $minValue
	 * @param  int|float|string $maxValue
	 */
	public function __construct($identifier, $minValue, $maxValue)
	{
		$this->identifier = $identifier;
		$this->minValue = $minValue;
		$this->maxValue = $maxValue;
	}

	/**
	 * Return "where" parts
	 *
	 * @return array
	 */
	public function getExpressionData()
	{
		return array(
			array(
				$this->specification,
				array($this->identifier, $this->minValue, $this->maxValue),
				array(self::TYPE_IDENTIFIER, self::TYPE_VALUE, self::TYPE_VALUE),
			),
		);
	}
}
