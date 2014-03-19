<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Sender;

use Protocol\Http\Headers;

class Http extends Standard
{
	/**#@+
	 * @const int Status codes
	 */
	const STATUS_CODE_100 = 100;
	const STATUS_CODE_101 = 101;
	const STATUS_CODE_102 = 102;
	const STATUS_CODE_200 = 200;
	const STATUS_CODE_201 = 201;
	const STATUS_CODE_202 = 202;
	const STATUS_CODE_203 = 203;
	const STATUS_CODE_204 = 204;
	const STATUS_CODE_205 = 205;
	const STATUS_CODE_206 = 206;
	const STATUS_CODE_207 = 207;
	const STATUS_CODE_208 = 208;
	const STATUS_CODE_300 = 300;
	const STATUS_CODE_301 = 301;
	const STATUS_CODE_302 = 302;
	const STATUS_CODE_303 = 303;
	const STATUS_CODE_304 = 304;
	const STATUS_CODE_305 = 305;
	const STATUS_CODE_306 = 306;
	const STATUS_CODE_307 = 307;
	const STATUS_CODE_400 = 400;
	const STATUS_CODE_401 = 401;
	const STATUS_CODE_402 = 402;
	const STATUS_CODE_403 = 403;
	const STATUS_CODE_404 = 404;
	const STATUS_CODE_405 = 405;
	const STATUS_CODE_406 = 406;
	const STATUS_CODE_407 = 407;
	const STATUS_CODE_408 = 408;
	const STATUS_CODE_409 = 409;
	const STATUS_CODE_410 = 410;
	const STATUS_CODE_411 = 411;
	const STATUS_CODE_412 = 412;
	const STATUS_CODE_413 = 413;
	const STATUS_CODE_414 = 414;
	const STATUS_CODE_415 = 415;
	const STATUS_CODE_416 = 416;
	const STATUS_CODE_417 = 417;
	const STATUS_CODE_418 = 418;
	const STATUS_CODE_422 = 422;
	const STATUS_CODE_423 = 423;
	const STATUS_CODE_424 = 424;
	const STATUS_CODE_425 = 425;
	const STATUS_CODE_426 = 426;
	const STATUS_CODE_428 = 428;
	const STATUS_CODE_429 = 429;
	const STATUS_CODE_431 = 431;
	const STATUS_CODE_500 = 500;
	const STATUS_CODE_501 = 501;
	const STATUS_CODE_502 = 502;
	const STATUS_CODE_503 = 503;
	const STATUS_CODE_504 = 504;
	const STATUS_CODE_505 = 505;
	const STATUS_CODE_506 = 506;
	const STATUS_CODE_507 = 507;
	const STATUS_CODE_508 = 508;
	const STATUS_CODE_511 = 511;
	/**#@-*/

	/**#@+
	 * @const string Version constant numbers
	 */
	const VERSION_10 = '1.0';
	const VERSION_11 = '1.1';
	/**#@-*/

	/**
	 * @var array Reason Phrases
	 */
	protected $reasonPhrases = array( // INFORMATIONAL CODES
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing', // SUCCESS CODES
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-status',
		208 => 'Already Reported', // REDIRECTION CODES
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy', // Deprecated
		307 => 'Temporary Redirect', // CLIENT ERROR
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Time-out',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Large',
		415 => 'Unsupported Media Type',
		416 => 'Requested range not satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Unordered Collection',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large', // SERVER ERROR
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Time-out',
		505 => 'HTTP Version not supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		511 => 'Network Authentication Required'
	);

	/**
	 * @var string
	 */
	protected $version = self::VERSION_11;

	/**
	 * @var string
	 */
	protected $statusReason;

	/**
	 * @var Headers
	 */
	protected $headers;

	/**
	 * Set the HTTP version for this object, one of 1.0 or 1.1
	 *
	 * @param $version
	 * @return $this
	 * @throws Exception\InvalidArgumentException
	 */
	public function setVersion($version)
	{
		if ($version != self::VERSION_10 && $version != self::VERSION_11) {
			throw new Exception\InvalidArgumentException('Not valid or not supported HTTP version: ' . $version);
		}
		$this->version = $version;
		return $this;
	}

	/**
	 * Return the HTTP version for this request
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * Set status code and (optionally) message
	 *
	 * @param int $code
	 * @param null|string $reason
	 * @throws Exception\InvalidArgumentException
	 * @return $this
	 */
	public function setStatus($code, $reason = null)
	{
		$const = get_called_class() . '::STATUS_CODE_' . $code;
		if (!is_numeric($code) || !defined($const)) {
			$code = is_scalar($code) ? $code : gettype($code);
			throw new Exception\InvalidArgumentException(sprintf('Invalid status code provided: "%s"', $code));
		}
		$this->statusCode = (int)$code;
		$this->statusReason = $reason;
		return $this;
	}

	/**
	 * Retrieve status code
	 *
	 * @return int
	 */
	public function getStatusCode()
	{
		return $this->statusCode;
	}

	/**
	 * Get status reason
	 *
	 * @return string
	 */
	public function getStatusReason()
	{
		return $this->statusReason ? : $this->reasonPhrases[$this->statusCode];
	}

	/**
	 * Provide an headers
	 *
	 * @param  Headers $headers
	 * @return $this
	 */
	public function setHeaders(Headers $headers)
	{
		$this->headers = $headers;
		return $this;
	}

	/**
	 * Return the header container responsible for headers
	 *
	 * @return Headers
	 */
	public function getHeaders()
	{
		if ($this->headers === null) {
			$this->headers = new Headers;
		}

		return $this->headers;
	}

	/**
	 * @return bool
	 */
	public function isRedirect()
	{
		return $this->getHeaders()->has('Location');
	}

	/**
	 * Set a cookie
	 *
	 * @param $name
	 * @param mixed $value
	 * @param int $expire
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure
	 * @param bool $httponly
	 * @return $this
	 */
	public function setCookie($name, $value = null, $expire = 0, $path = null, $domain = null,
		$secure = false, $httponly = false)
	{
		$value = $name . '=' . ((string)$value);
		if (is_int($expire) && $expire) {
			$value .= '; expires=' . gmdate('D,d M Y H:i:s', $expire) . ' GMT';
		}
		if (!is_null($path)) {
			$value .= '; path=' . ((string)$path);
		}
		if (!is_null($domain)) {
			$value .= '; domain=' . ((string)$domain);
		}
		if ($secure) {
			$value .= '; secure';
		}
		if ($httponly) {
			$value .= '; httponly';
		}
		$this->getHeaders()->addHeaderLine('Set-Cookie', $value);

		return $this;
	}

	/**
	 * Send headers
	 */
	protected function beforeSend()
	{
		if ($this->sent || headers_sent()) {
			return;
		}

		foreach ($this->getHeaders() as $header) {
			header((string)$header);
		}

		$status = trim(sprintf(
			'HTTP/%s %d %s',
			$this->getVersion(), $this->getStatusCode(), $this->getStatusReason()
		));

		header($status);
	}
}