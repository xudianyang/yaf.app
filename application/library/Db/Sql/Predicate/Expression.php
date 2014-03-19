<?php
/**
 * Api.cloud
 *
 * @copyright Copyright (c) 2013 Beijing CmsTop Technology Co.,Ltd. (http://www.cmstop.com)
 */

namespace Db\Sql\Predicate;

use Db\Sql\Expression as BaseExpression;

class Expression extends BaseExpression implements PredicateInterface
{

	/**
	 * Constructor
	 *
	 * @param string $expression
	 * @param int|float|bool|string|array $valueParameter
	 */
	public function __construct($expression = null, $valueParameter = null /*[, $valueParameter, ... ]*/)
	{
		if ($expression) {
			$this->setExpression($expression);
		}

		if (is_array($valueParameter)) {
			$this->setParameters($valueParameter);
		} else {
			$argNum = func_num_args();
			if ($argNum > 2 || is_scalar($valueParameter)) {
				$parameters = array();
				for ($i = 1; $i < $argNum; $i++) {
					$parameters[] = func_get_arg($i);
				}
				$this->setParameters($parameters);
			}
		}
	}

}
