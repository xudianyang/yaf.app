<?php
/**
 * Api.cloud
 *
 * @copyright Copyright (c) 2013 Beijing CmsTop Technology Co.,Ltd. (http://www.cmstop.com)
 */

namespace Db\Sql\Predicate;

class Like implements PredicateInterface
{

	protected $specification = '%1$s LIKE %2$s';
	protected $identifier = '';
	protected $like = '';

	/**
	 * @param string $identifier
	 * @param string $like
	 */
	public function __construct($identifier, $like)
	{
		$this->identifier = $identifier;
		$this->like = $like;
	}

	/**
	 * @return array
	 */
	public function getExpressionData()
	{
		return array(
			array(
				$this->specification, array($this->identifier, $this->like),
				array(self::TYPE_IDENTIFIER, self::TYPE_VALUE)
			)
		);
	}

}
