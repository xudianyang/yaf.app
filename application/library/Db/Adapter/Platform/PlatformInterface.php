<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Db\Adapter\Platform;

interface PlatformInterface
{
	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Get quote identifier symbol
	 *
	 * @return string
	 */
	public function getQuoteIdentifierSymbol();

	/**
	 * Quote identifier
	 *
	 * @param  string $identifier
	 * @return string
	 */
	public function quoteIdentifier($identifier);

	/**
	 * Quote identifier chain
	 *
	 * @param string|string[] $identifierChain
	 * @return string
	 */
	public function quoteIdentifierChain($identifierChain);

	/**
	 * Get quote value symbol
	 *
	 * @return string
	 */
	public function getQuoteValueSymbol();

	/**
	 * Quote value
	 *
	 * @param  string $value
	 * @return string
	 */
	public function quoteValue($value);

	/**
	 * Quote Limit-Offset value
	 *
	 * @param  string $value
	 * @return string
	 */
	public function quoteLimitOffset($value);

	/**
	 * Quote value list
	 *
	 * @param string|string[] $valueList
	 * @return string
	 */
	public function quoteValueList($valueList);

	/**
	 * Get identifier separator
	 *
	 * @return string
	 */
	public function getIdentifierSeparator();

	/**
	 * Quote identifier in fragment
	 *
	 * @param string $identifier
	 * @param array $safeWords
	 * @return string
	 */
	public function quoteIdentifierInFragment($identifier, array $safeWords = array());
}
