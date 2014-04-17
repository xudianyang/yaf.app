<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Db\Sql;

use Db\Adapter\Driver;
use Db\Adapter\Parameters;
use Db\Adapter\Platform\PlatformInterface;

abstract class AbstractSql implements SqlInterface
{
	/**
	 * @var array
	 */
	protected $specifications = array();

	/**
	 * @var string
	 */
	protected $processInfo = array('paramPrefix' => '', 'subselectCount' => 0);

	/**
	 * @var array
	 */
	protected $instanceParameterIndex = array();

	/** @var PlatformInterface */
	protected $platform;

	/** @var  Driver\DriverInterface */
	protected $driver;

	protected function processExpression(ExpressionInterface $expression, PlatformInterface $platform,
		Driver\DriverInterface $driver = null, $namedParameterPrefix = null, Parameters $parameters = null)
	{
		// static counter for the number of times this method was invoked across the PHP runtime
		static $runtimeExpressionPrefix = 0;

		if ($driver && ((!is_string($namedParameterPrefix) || $namedParameterPrefix == ''))) {
			$namedParameterPrefix = sprintf('expr%04dParam', ++$runtimeExpressionPrefix);
		}

		$sql = '';

		// initialize variables
		$parts = $expression->getExpressionData();

		if (!isset($this->instanceParameterIndex[$namedParameterPrefix])) {
			$this->instanceParameterIndex[$namedParameterPrefix] = 1;
		}

		$expressionParamIndex = & $this->instanceParameterIndex[$namedParameterPrefix];

		foreach ($parts as $part) {

			// if it is a string, simply tack it onto the return sql "specification" string
			if (is_string($part)) {
				$sql .= $part;
				continue;
			}

			if (!is_array($part)) {
				throw new Exception\RuntimeException(
					'Elements returned from getExpressionData() array must be a string or array.');
			}

			// process values and types (the middle and last position of the expression data)
			$values = $part[1];
			$types = (isset($part[2])) ? $part[2] : array();
			foreach ($values as $vIndex => $value) {
				if (isset($types[$vIndex]) && $types[$vIndex] == ExpressionInterface::TYPE_IDENTIFIER) {
					$values[$vIndex] = $platform->quoteIdentifierInFragment($value);
				} elseif (isset($types[$vIndex]) && $types[$vIndex] == ExpressionInterface::TYPE_VALUE
					&& $value instanceof Select
				) {
					// process sub-select
					if ($driver) {
						$values[$vIndex] = '(' . $this->processSubSelect($value, $platform, $driver, $parameters) . ')';
					} else {
						$values[$vIndex] = '(' . $this->processSubSelect($value, $platform) . ')';
					}
				} elseif (isset($types[$vIndex]) && $types[$vIndex] == ExpressionInterface::TYPE_VALUE
					&& $value instanceof ExpressionInterface
				) {
					$sql = $this->processExpression($value, $platform, $driver,
						$namedParameterPrefix . $vIndex . 'subpart', $parameters);
					$values[$vIndex] = $sql;
				} elseif (isset($types[$vIndex]) && $types[$vIndex] == ExpressionInterface::TYPE_VALUE) {

					if ($driver) {
						$name = $namedParameterPrefix . $expressionParamIndex++;
						$parameters->offsetSet($name, $value);
						$values[$vIndex] = $driver->formatParameterName($name);
						continue;
					}

					// if not a preparable statement, simply quote the value and move on
					$values[$vIndex] = $platform->quoteValue($value);
				} elseif (isset($types[$vIndex]) && $types[$vIndex] == ExpressionInterface::TYPE_LITERAL) {
					$values[$vIndex] = $value;
				}
			}

			// after looping the values, interpolate them into the sql string (they might be placeholder names, or values)
			$sql .= vsprintf($part[0], $values);
		}

		return $sql;
	}

	/**
	 * @param $specifications
	 * @param $parameters
	 * @return string
	 * @throws Exception\RuntimeException
	 */
	protected function createSqlFromSpecificationAndParameters($specifications, $parameters)
	{
		if (is_string($specifications)) {
			return vsprintf($specifications, $parameters);
		}

		$parametersCount = count($parameters);
		foreach ($specifications as $specificationString => $paramSpecs) {
			if ($parametersCount == count($paramSpecs)) {
				break;
			}
			unset($specificationString, $paramSpecs);
		}

		if (!isset($specificationString)) {
			throw new Exception\RuntimeException(
				'A number of parameters was found that is not supported by this specification'
			);
		}

		$topParameters = array();
		foreach ($parameters as $position => $paramsForPosition) {
			if (isset($paramSpecs[$position]['combinedby'])) {
				$multiParamValues = array();
				foreach ($paramsForPosition as $multiParamsForPosition) {
					$ppCount = count($multiParamsForPosition);
					if (!isset($paramSpecs[$position][$ppCount])) {
						throw new Exception\RuntimeException(
							'A number of parameters (' . $ppCount .
								') was found that is not supported by this specification');
					}
					$multiParamValues[] = vsprintf($paramSpecs[$position][$ppCount], $multiParamsForPosition);
				}
				$topParameters[] = implode($paramSpecs[$position]['combinedby'], $multiParamValues);
			} elseif ($paramSpecs[$position] !== null) {
				$ppCount = count($paramsForPosition);
				if (!isset($paramSpecs[$position][$ppCount])) {
					throw new Exception\RuntimeException(
						'A number of parameters (' . $ppCount .
							') was found that is not supported by this specification');
				}
				$topParameters[] = vsprintf($paramSpecs[$position][$ppCount], $paramsForPosition);
			} else {
				$topParameters[] = $paramsForPosition;
			}
		}
		return vsprintf($specificationString, $topParameters);
	}

	protected function processSubSelect(Select $subselect, PlatformInterface $platform,
		Driver\DriverInterface $driver = null, Parameters $parameters = null)
	{
		if ($driver) {
			// Track subselect prefix and count for parameters
			$this->processInfo['subselectCount']++;
			$subselect->processInfo['subselectCount'] = $this->processInfo['subselectCount'];
			$subselect->processInfo['paramPrefix'] = 'subselect' . $subselect->processInfo['subselectCount'];

			// call subselect
			$stmt = $subselect->prepareStatement($platform, $driver);

			// copy count
			$this->processInfo['subselectCount'] = $subselect->processInfo['subselectCount'];

			if ($parameters) {
				$parameters->merge($stmt->getParameters());
			}
			$sql = $stmt->getSql();
		} else {
			$sql = $subselect->getSqlString($platform);
		}
		return $sql;
	}

	/**
	 * Set a default platform
	 *
	 * @param PlatformInterface $platform
	 * @return $this
	 */
	public function setPlatform(PlatformInterface $platform)
	{
		$this->platform = $platform;
		return $this;
	}

	/**
	 * Set a default driver
	 *
	 * @param Driver\DriverInterface $driver
	 * @return $this
	 */
	public function setDriver(Driver\DriverInterface $driver)
	{
		$this->driver = $driver;
		return $this;
	}

	/**
	 * @param null|array|Parameters $parameters
	 * @return Driver\ResultInterface
	 */
	public function execute($parameters = null)
	{
		return $this->prepareStatement()->execute($parameters);
	}
}
