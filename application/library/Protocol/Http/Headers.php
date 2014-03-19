<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Protocol\Http;

class Headers implements \Countable, \Iterator
{
	/**
	 * @var array key names for $headers array
	 */
	protected $headersKeys = array();

	/**
	 * @var array Array of header array information or Header instances
	 */
	protected $headers = array();

	/**
	 * Add many headers at once
	 * Expects an array (or Traversable object) of type/value pairs.
	 *
	 * @param  array|\Traversable $headers
	 * @return $this
	 * @throws Exception\InvalidArgumentException
	 */
	public function addHeaders($headers)
	{
		if (!is_array($headers) && !$headers instanceof \Traversable) {
			throw new Exception\InvalidArgumentException(sprintf(
				'Expected array or Traversable; received "%s"',
				is_object($headers) ? get_class($headers) : gettype($headers)
			));
		}

		foreach ($headers as $name => $value) {
			if (is_string($name)) {
				$this->addHeaderLine($name, $value);
			} else {
				if (is_string($value)) {
					$this->addHeaderLine($value);
				} elseif ($value instanceof Header) {
					$this->addHeader($value);
				} elseif (is_array($value) && count($value) == 1) {
					$this->addHeaderLine(key($value), current($value));
				} elseif (is_array($value) && count($value) == 2) {
					$this->addHeaderLine($value[0], $value[1]);
				}
			}
		}

		return $this;
	}

	/**
	 * Add a raw header line, either in name => value, or as a single string 'name: value'
	 *
	 * @throws Exception\InvalidArgumentException
	 * @param string $header field name or header line
	 * @param string $value  optional
	 * @return $this
	 */
	public function addHeaderLine($header, $value = null)
	{
		$matches = null;
		if (preg_match('/^(?P<name>[^()><@,;:\"\\/\[\]?=}{ \t]+):.*$/', $header, $matches) && $value === null) {
			// is a header
			$headerName = $matches['name'];
			$headerKey = static::createKey($matches['name']);
			$line = $header;
		} elseif ($value === null) {
			throw new Exception\InvalidArgumentException('A field name was provided without a field value');
		} else {
			$headerName = $header;
			$headerKey = static::createKey($header);
			if (is_array($value)) {
				$value = implode(', ', $value);
			}
			$line = $header . ': ' . $value;
		}

		$this->headersKeys[] = $headerKey;
		$this->headers[] = array('name' => $headerName, 'line' => $line);

		return $this;
	}

	/**
	 * Add a Header to this container
	 *
	 * @param  Header $header
	 * @return $this
	 */
	public function addHeader(Header $header)
	{
		$this->headersKeys[] = static::createKey($header->getName());
		$this->headers[] = $header;

		return $this;
	}

	/**
	 * Remove a Header from the container
	 *
	 * @param Header $header
	 * @return bool
	 */
	public function removeHeader(Header $header)
	{
		$index = array_search($header, $this->headers, true);
		if ($index !== false) {
			unset($this->headersKeys[$index]);
			unset($this->headers[$index]);

			return true;
		}
		return false;
	}

	/**
	 * Clear all headers
	 * Removes all headers from queue
	 *
	 * @return $this
	 */
	public function clearHeaders()
	{
		$this->headers = $this->headersKeys = array();
		return $this;
	}

	/**
	 * Get all headers of a certain name/type
	 *
	 * @param  string $name
	 * @return null|array
	 */
	public function get($name)
	{
		$key = static::createKey($name);
		if (!in_array($key, $this->headersKeys)) {
			return null;
		}

		$headers = array();

		foreach (array_keys($this->headersKeys, $key) as $index) {
			if (is_array($this->headers[$index])) {
				$this->lazyLoadHeader($index);
			}
			$headers[] = $this->headers[$index];
		}

		return $headers;
	}

	/**
	 * Test for existence of a type of header
	 *
	 * @param  string $name
	 * @return bool
	 */
	public function has($name)
	{
		return in_array(static::createKey($name), $this->headersKeys);
	}

	/**
	 * Advance the pointer for this object as an interator
	 *
	 * @return void
	 */
	public function next()
	{
		next($this->headers);
	}

	/**
	 * Return the current key for this object as an iterator
	 *
	 * @return mixed
	 */
	public function key()
	{
		return (key($this->headers));
	}

	/**
	 * Is this iterator still valid?
	 *
	 * @return bool
	 */
	public function valid()
	{
		return (current($this->headers) !== false);
	}

	/**
	 * Reset the internal pointer for this object as an iterator
	 *
	 * @return void
	 */
	public function rewind()
	{
		reset($this->headers);
	}

	/**
	 * Return the current value for this iterator, lazy loading it if need be
	 *
	 * @return array|Header
	 */
	public function current()
	{
		$current = current($this->headers);
		if (is_array($current)) {
			$current = $this->lazyLoadHeader(key($this->headers));
		}
		return $current;
	}

	/**
	 * Return the number of headers in this contain, if all headers have not been parsed, actual count could
	 * increase if MultipleHeader objects exist in the Request/Response.  If you need an exact count, iterate
	 *
	 * @return int count of currently known headers
	 */
	public function count()
	{
		return count($this->headers);
	}

	/**
	 * @param $index
	 * @return mixed|void
	 */
	protected function lazyLoadHeader($index)
	{
		$current = $this->headers[$index];

		$current = Header::fromString($current['line']);

		$this->headers[$index] = $current;

		return $current;
	}

	/**
	 * Create array key from header name
	 *
	 * @param string $name
	 * @return string
	 */
	protected static function createKey($name)
	{
		return str_replace(array('-', '_', ' ', '.'), '', strtolower($name));
	}
}
