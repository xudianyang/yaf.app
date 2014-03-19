<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Input;

use Top\Uri\Http as HttpUri;
use Router\RouteMatch;
use Top\Uri\Exception as UriException;
use Exception;
use Traversable;

class Http extends Standard
{
	/**
	 * @var string
	 */
	protected $method = null;

	/**
	 * @var HttpUri
	 */
	protected $uri = null;

	/**
	 * @var string
	 */
	protected $baseUrl = '';

	/**
	 * @var string
	 */
	protected $basePath = '';

	/**
	 * @var string
	 */
	protected $pathInfo = '';

	/**
	 * @var RouteMatch
	 */
	protected $routeMatch = null;

	/**
	 * @var array
	 */
	protected $params = array();

	public function __construct(array $server = null)
	{
		$uriParams = $this->fromServer($server ? : $_SERVER);
		$uri = new HttpUri();
		$uri->setScheme($uriParams['scheme']);
		$uri->setHost($uriParams['host']);
		$uri->setPort($uriParams['port']);
		$uri->setQuery($uriParams['query']);
		$uri->setPath($uriParams['path']);
		$this->setUri($uri);
		$this->setBaseUrl($uriParams['baseUrl']);
		$this->setBasePath($uriParams['basePath']);
		$this->setPathInfo($uriParams['pathInfo']);
	}

	protected function fromServer($server)
	{
		// Get uri full path
		$path = $server['REQUEST_URI'];
		if (($pos = strpos($path, '?')) !== false) {
			$path = substr($path, 0, $pos);
		}

		// Get script name
		$scriptName = '';
		$baseName = isset($server['SCRIPT_FILENAME']) ? basename($server['SCRIPT_FILENAME']) : '';
		if (isset($server['SCRIPT_NAME']) && basename($server['SCRIPT_NAME']) === $baseName) {
			$scriptName = $server['SCRIPT_NAME'];
		} elseif (isset($server['PHP_SELF']) && basename($server['PHP_SELF']) === $baseName) {
			$scriptName = $server['PHP_SELF'];
		} else {
			/*
			 PHP_SELF: /path/to/index.php/xxx or /abc/path/to/index.php/xxx
			 SCRIPT_FILENAME: /usr/local/xxx/path/to/index.php
			 RESULT: /path/to/index.php
			*/
			$self = isset($server['PHP_SELF']) ? $server['PHP_SELF'] : '';
			$file = isset($server['SCRIPT_FILENAME']) ? trim($server['SCRIPT_FILENAME'], '/') : '';
			$segs = array_reverse(explode('/', $file));
			$index = 0;
			$last = count($segs);
			while ($last > $index) {
				$temp = '/' . $segs[$index++] . $scriptName;
				$pos = strpos($self, $temp);
				if ($pos === false) {
					break;
				}
				$scriptName = $temp;
				if ($pos == 0) {
					break;
				}
			}
		}

		$baseUrl = '';
		$basePath = '';
		if ($scriptName !== '') {
			/*
			 URI(path): /path/to/index.php | /path/to/index.php/xxx
			 SCRIPT_NAME: /path/to/index.php
			*/
			if (0 === strpos($path, $scriptName)) {
				$baseUrl = $scriptName;
				if (basename($baseUrl) == $baseName) {
					$basePath = rtrim(str_replace('\\', '/', dirname($baseUrl)), '/');
				} else {
					$basePath = $baseUrl;
				}
			} else {
				/*
				 URI(path): /path/to | /path/to/xxx
				 SCRIPT_NAME: /path/to/index.php
				 basePath: /path/to
				*/
				$dirname = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
				if ($dirname !== '' && 0 === strpos($path, $dirname)) {
					$baseUrl = $basePath = $dirname;
				} elseif (($scriptBaseName = basename($scriptName)) !== '' && strpos($path, $scriptBaseName)) {
					$baseUrl = $scriptName;
					/*
					 URI: /xxx/path/to/index.php/xxx
					 SCRIPT_NAME: /path/to/index.php
					*/
					if (strlen($path) >= strlen($scriptName) && ($pos = strpos($path, $scriptName))) {
						$baseUrl = substr($path, 0, $pos + strlen($scriptName));
					}
					if (basename($baseUrl) == $baseName) {
						$basePath = rtrim(str_replace('\\', '/', dirname($baseUrl)), '/');
					} else {
						$basePath = $baseUrl;
					}
				}
			}
		}
		$pathInfo = $path;
		if ($baseUrl !== '' && strpos($pathInfo, $baseUrl) === 0) {
			$pathInfo = substr($pathInfo, strlen($baseUrl));
		}

		return array(
			'scheme' => isset($server['HTTPS']) && $server['HTTPS'] == 'on' ? 'https' : 'http',
			'host' => $server['SERVER_NAME'],
			'port' => $server['SERVER_PORT'],
			'query' => $server['QUERY_STRING'],
			'path' => $path,
			'baseUrl' => $baseUrl,
			'basePath' => $basePath,
			'pathInfo' => $pathInfo
		);
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		switch (true) {
			case isset($this->params[$key]):
				return $this->params[$key];
			case isset($_GET[$key]):
				return $_GET[$key];
			case isset($_POST[$key]):
				return $_POST[$key];
			case isset($_COOKIE[$key]):
				return $_COOKIE[$key];
			case ($key == 'URI'):
				return $this->getUri();
			case ($key == 'PATH_INFO'):
				return $this->getPathInfo();
			case ($key == 'BASE_PATH'):
				return $this->getBasePath();
			case ($key == 'BASE_URL'):
				return $this->getBaseUrl();
			case isset($_SERVER[$key]):
				return $_SERVER[$key];
			case isset($_ENV[$key]):
				return $_ENV[$key];
			default:
				return $default;
		}
	}

	/**
	 * @param $key string
	 * @return bool
	 */
	public function has($key)
	{
		switch (true) {
			case isset($this->params[$key]):
				return true;
			case isset($_GET[$key]):
				return true;
			case isset($_POST[$key]):
				return true;
			case isset($_COOKIE[$key]):
				return true;
			case isset($_SERVER[$key]):
				return true;
			case isset($_ENV[$key]):
				return true;
			default:
				return false;
		}
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return array_merge($_SERVER, $_COOKIE, $_POST, $_GET, $this->params);
	}

	/**
	 * @param RouteMatch $match
	 * @return $this
	 */
	public function setRouteMatch(RouteMatch $match)
	{
		$this->routeMatch = $match;
		$this->params = array_merge($match->getParams(), $this->params);
		return $this;
	}

	/**
	 * @return null|RouteMatch
	 */
	public function getRouteMatch()
	{
		return $this->routeMatch;
	}

	/**
	 * @param HttpUri $uri
	 * @return $this
	 */
	public function setUri(HttpUri $uri)
	{
		$this->uri = $uri;
		return $this;
	}

	/**
	 * Return the URI for this request object
	 *
	 * @return HttpUri
	 */
	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * Return the URI for this request object as a string
	 *
	 * @return string
	 */
	public function getUriString()
	{
		return $this->uri->toString();
	}

	/**
	 * @param string $baseUrl
	 * @return $this
	 */
	public function setBaseUrl($baseUrl)
	{
		$this->baseUrl = (string)$baseUrl;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getBaseUrl()
	{
		return $this->baseUrl;
	}

	/**
	 * @param string $basePath
	 * @return $this
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = $basePath;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getBasePath()
	{
		return $this->basePath;
	}

	/**
	 * @param string $pathInfo
	 * @return $this
	 */
	public function setPathInfo($pathInfo)
	{
		$this->pathInfo = $pathInfo;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPathInfo()
	{
		return $this->pathInfo;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->getPathInfo();
	}

	/**
	 * Retrieve a member of the $_GET superglobal
	 * If no $key is passed, returns the entire $_GET array.
	 *
	 * @param string $key
	 * @param mixed $default Default value to use if key not found
	 * @return mixed Returns null if key does not exist
	 */
	public function getQuery($key = null, $default = null)
	{
		if (null === $key) {
			return $_GET;
		}
		return isset($_GET[$key]) ? $_GET[$key] : $default;
	}

	/**
	 * Retrieve a member of the $_POST superglobal
	 * If no $key is passed, returns the entire $_POST array.
	 *
	 * @param string $key
	 * @param mixed $default Default value to use if key not found
	 * @return mixed Returns null if key does not exist
	 */
	public function getPost($key = null, $default = null)
	{
		if (null === $key) {
			return $_POST;
		}
		return isset($_POST[$key]) ? $_POST[$key] : $default;
	}

	/**
	 * Retrieve a member of the $_COOKIE superglobal
	 * If no $key is passed, returns the entire $_COOKIE array.
	 *
	 * @param string $key
	 * @param mixed $default Default value to use if key not found
	 * @return mixed Returns null if key does not exist
	 */
	public function getCookie($key = null, $default = null)
	{
		if (null === $key) {
			return $_COOKIE;
		}
		return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
	}

	/**
	 * Retrieve a member of the $_FILES superglobal
	 * If no $key is passed, returns the entire $_FILES array.
	 *
	 * @param string $key
	 * @return mixed Returns null if key does not exist
	 */
	public function getFile($key = null)
	{
		if ($key === null) {
			return $_FILES;
		}
		return isset($_FILES[$key]) ? $_FILES[$key] : null;
	}

	/**
	 * Return the header
	 *
	 * @param string|null $name    Header name to retrieve, or null to get the whole container.
	 * @param mixed|null $default Default value to use when the requested header is missing.
	 * @return mixed
	 */
	public function getHeader($name = null, $default = false)
	{
		// Try to get it from the $_SERVER array first
		$temp = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
		if (!empty($_SERVER[$temp])) {
			return $_SERVER[$temp];
		}

		// This seems to be the only way to get the Authorization header on
		// Apache
		if (function_exists('apache_request_headers')) {
			$headers = apache_request_headers();
			if (!empty($headers[$name])) {
				return $headers[$name];
			}
		}

		return $default;
	}

	/**
	 * Is this an OPTIONS method request?
	 *
	 * @return bool
	 */
	public function isOptions()
	{
		return ($this->getMethod() === 'OPTIONS');
	}

	/**
	 * Is this a PROPFIND method request?
	 *
	 * @return bool
	 */
	public function isPropFind()
	{
		return ($this->getMethod() === 'PROPFIND');
	}

	/**
	 * Is this a GET method request?
	 *
	 * @return bool
	 */
	public function isGet()
	{
		return ($this->getMethod() === 'GET');
	}

	/**
	 * Is this a HEAD method request?
	 *
	 * @return bool
	 */
	public function isHead()
	{
		return ($this->getMethod() === 'HEAD');
	}

	/**
	 * Is this a POST method request?
	 *
	 * @return bool
	 */
	public function isPost()
	{
		return ($this->getMethod() === 'POST');
	}

	/**
	 * Is this a PUT method request?
	 *
	 * @return bool
	 */
	public function isPut()
	{
		return ($this->getMethod() === 'PUT');
	}

	/**
	 * Is this a DELETE method request?
	 *
	 * @return bool
	 */
	public function isDelete()
	{
		return ($this->getMethod() === 'DELETE');
	}

	/**
	 * Is this a TRACE method request?
	 *
	 * @return bool
	 */
	public function isTrace()
	{
		return ($this->getMethod() === 'TRACE');
	}

	/**
	 * Is this a CONNECT method request?
	 *
	 * @return bool
	 */
	public function isConnect()
	{
		return ($this->getMethod() === 'CONNECT');
	}

	/**
	 * Is this a PATCH method request?
	 *
	 * @return bool
	 */
	public function isPatch()
	{
		return ($this->getMethod() === 'PATCH');
	}

	/**
	 * Is the request a Javascript XMLHttpRequest?
	 *
	 * @return bool
	 */
	public function isXmlHttpRequest()
	{
		return $this->getHeader('X_REQUESTED_WITH') === 'XMLHttpRequest';
	}

	/**
	 * Is this a Flash request?
	 *
	 * @return bool
	 */
	public function isFlashRequest()
	{
		$header = $this->getHeader('USER_AGENT');
		return $header && stristr($header, ' flash');
	}
}