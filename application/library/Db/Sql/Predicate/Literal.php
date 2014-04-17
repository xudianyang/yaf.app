<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
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
