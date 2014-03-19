<?php
/**
 * Api.cloud
 *
 * @copyright Copyright (c) 2013 Beijing CmsTop Technology Co.,Ltd. (http://www.cmstop.com)
 */

namespace Db\Sql\Predicate;

class Literal implements PredicateInterface
{
	protected $literal = '';

	public function __construct($literal)
	{
		$this->literal = $literal;
	}

	/**
	 * @return array
	 */
	public function getExpressionData()
	{
		return array(
			array(
				str_replace('%', '%%', $this->literal),
				array(),
				array()
			)
		);
	}
}
