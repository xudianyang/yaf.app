<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Protocol\Redis;

/**
 * A lightweight Redis PHP standalone client
 * Server/Connection:
 * @method Client        pipeline()
 * @method Client        multi()
 * @method array         exec()
 * @method string        flushAll()
 * @method string        flushDb()
 * @method array         info()
 * @method bool|array    config(string $setGet, string $key, string $value = null)
 * Keys:
 * @method int           del(string $key)
 * @method int           exists(string $key)
 * @method int           expire(string $key, int $seconds)
 * @method int           expireAt(string $key, int $timestamp)
 * @method array         keys(string $key)
 * @method int           persist(string $key)
 * @method bool          rename(string $key, string $newKey)
 * @method bool          renameNx(string $key, string $newKey)
 * @method array         sort(string $key, string $arg1, string $valueN = null)
 * @method int           ttl(string $key)
 * @method string        type(string $key)
 * Scalars:
 * @method int           append(string $key, string $value)
 * @method int           decr(string $key)
 * @method int           decrBy(string $key, int $decrement)
 * @method bool|string   get(string $key)
 * @method int           getBit(string $key, int $offset)
 * @method string        getRange(string $key, int $start, int $end)
 * @method string        getSet(string $key, string $value)
 * @method int           incr(string $key)
 * @method int           incrBy(string $key, int $decrement)
 * @method array         mGet(array $keys)
 * @method bool          mSet(array $keysValues)
 * @method int           mSetNx(array $keysValues)
 * @method bool          set(string $key, string $value)
 * @method int           setBit(string $key, int $offset, int $value)
 * @method bool          setEx(string $key, int $seconds, string $value)
 * @method int           setNx(string $key, string $value)
 * @method int           setRange(string $key, int $offset, int $value)
 * @method int           strLen(string $key)
 * Sets:
 * @method int           sAdd(string $key, mixed $value, string $valueN = null)
 * @method int           sRem(string $key, mixed $value, string $valueN = null)
 * @method array         sMembers(string $key)
 * @method array         sUnion(mixed $keyOrArray, string $valueN = null)
 * @method array         sInter(mixed $keyOrArray, string $valueN = null)
 * @method array         sDiff(mixed $keyOrArray, string $valueN = null)
 * Hashes:
 * @method bool|int      hSet(string $key, string $field, string $value)
 * @method bool          hSetNx(string $key, string $field, string $value)
 * @method bool|string   hGet(string $key, string $field)
 * @method bool|int      hLen(string $key)
 * @method bool          hDel(string $key, string $field)
 * @method array         hKeys(string $key, string $field)
 * @method array         hVals(string $key, string $field)
 * @method array         hGetAll(string $key)
 * @method bool          hExists(string $key, string $field)
 * @method int           hIncrBy(string $key, string $field, int $value)
 * @method bool          hMSet(string $key, array $keysValues)
 * @method array         hMGet(string $key, array $fields)
 * Lists:
 * @method array|null    blPop(string $keyN, int $timeout)
 * @method array|null    brPop(string $keyN, int $timeout)
 * @method array|null    brPoplPush(string $source, string $destination, int $timeout)
 * @method string|null   lIndex(string $key, int $index)
 * @method int           lInsert(string $key, string $beforeAfter, string $pivot, string $value)
 * @method int           lLen(string $key)
 * @method string|null   lPop(string $key)
 * @method int           lPush(string $key, mixed $value, mixed $valueN = null)
 * @method int           lPushX(string $key, mixed $value)
 * @method array         lRange(string $key, int $start, int $stop)
 * @method int           lRem(string $key, int $count, mixed $value)
 * @method bool          lSet(string $key, int $index, mixed $value)
 * @method bool          lTrim(string $key, int $start, int $stop)
 * @method string|null   rPop(string $key)
 * @method string|null   rPoplPush(string $source, string $destination)
 * @method int           rPush(string $key, mixed $value, mixed $valueN = null)
 * @method int           rPushX(string $key, mixed $value)
 */
class Client
{
	const CRLF = "\r\n";

	const TYPE_STRING = 'string';
	const TYPE_LIST = 'list';
	const TYPE_SET = 'set';
	const TYPE_ZSET = 'zset';
	const TYPE_HASH = 'hash';
	const TYPE_NONE = 'none';
	const FREAD_BLOCK_SIZE = 8192;

	/**
	 * Socket connection to the Redis server
	 *
	 * @var resource
	 */
	protected $redis;
	protected $redisMulti;

	/**
	 * Host of the Redis server
	 *
	 * @var string
	 */
	protected $host;

	/**
	 * Port on which the Redis server is running
	 *
	 * @var integer
	 */
	protected $port;

	/**
	 * Timeout for connecting to Redis server
	 *
	 * @var float
	 */
	protected $timeout;

	/**
	 * Timeout for reading response from Redis server
	 *
	 * @var float
	 */
	protected $readTimeout;

	/**
	 * Unique identifier for persistent connections
	 *
	 * @var string
	 */
	protected $persistent;

	/**
	 * @var bool
	 */
	protected $connected = FALSE;

	/**
	 * @var int
	 */
	protected $maxConnectRetries = 0;

	/**
	 * @var int
	 */
	protected $connectFailures = 0;

	/**
	 * @var bool
	 */
	protected $usePipeline = FALSE;

	/**
	 * @var array
	 */
	protected $commandNames;

	/**
	 * @var string
	 */
	protected $commands;

	/**
	 * @var bool
	 */
	protected $isMulti = FALSE;

	/**
	 * @var bool
	 */
	protected $isWatching = FALSE;

	/**
	 * @var string
	 */
	protected $authPassword;

	/**
	 * @var int
	 */
	protected $selectedDb = 0;

	/**
	 * Aliases for backwards compatibility with phpredis
	 *
	 * @var array
	 */
	protected $aliasedMethods = array('delete' => 'del', 'getkeys' => 'keys', 'sremove' => 'srem');

	/**
	 * Creates a Redisent connection to the Redis server on host {@link $host} and port {@link $port}.
	 * $host may also be a path to a unix socket or a string in the form of tcp://[hostname]:[port] or unix://[path]
	 *
	 * @param string $host The hostname of the Redis server
	 * @param integer $port The port number of the Redis server
	 * @param float $timeout  Timeout period in seconds
	 * @param string $persistent  Flag to establish persistent connection
	 */
	public function __construct($host = '127.0.0.1', $port = 6379, $timeout = null, $persistent = '')
	{
		$this->host = (string)$host;
		$this->port = (int)$port;
		$this->timeout = $timeout;
		$this->persistent = (string)$persistent;
	}

	public function __destruct()
	{
		$this->close();
	}

	/**
	 * @param int $retries
	 * @return $this
	 */
	public function setMaxConnectRetries($retries)
	{
		$this->maxConnectRetries = $retries;
		return $this;
	}

	/**
	 * @throws Exception\RuntimeException
	 * @return $this
	 */
	public function connect()
	{
		if ($this->connected) {
			return $this;
		}
		if (preg_match('#^(tcp|unix)://(.*)$#', $this->host, $matches)) {
			if ($matches[1] == 'tcp') {
				if (!preg_match('#^(.*)(?::(\d+))?(?:/(.*))?$#', $matches[2], $matches)) {
					throw new Exception\InvalidArgumentException(
						'Invalid host format; expected tcp://host[:port][/persistent]'
					);
				}
				$this->host = $matches[1];
				$this->port = (int)(isset($matches[2]) ? $matches[2] : 6379);
				$this->persistent = isset($matches[3]) ? $matches[3] : '';
			} else {
				$this->host = $matches[2];
				$this->port = NULL;
				if (substr($this->host, 0, 1) != '/') {
					throw new Exception\InvalidArgumentException(
						'Invalid unix socket format; expected unix:///path/to/redis.sock'
					);
				}
			}
		}
		if ($this->port !== NULL && substr($this->host, 0, 1) == '/') {
			$this->port = NULL;
		}
		$flags = STREAM_CLIENT_CONNECT;
		$remote_socket = $this->port === NULL
			? 'unix://' . $this->host
			: 'tcp://' . $this->host . ':' . $this->port;
		if ($this->persistent) {
			if ($this->port === NULL) { // Unix socket
				throw new Exception\RuntimeException(
					'Persistent connections to UNIX sockets are not supported in standalone mode.'
				);
			}
			$remote_socket .= '/' . $this->persistent;
			$flags = $flags | STREAM_CLIENT_PERSISTENT;
		}
		$result = $this->redis = @stream_socket_client($remote_socket, $errno, $errstr,
			$this->timeout !== null ? $this->timeout : 2.5, $flags);

		// Use recursion for connection retries
		if (!$result) {
			$this->connectFailures++;
			if ($this->connectFailures <= $this->maxConnectRetries) {
				return $this->connect();
			}
			$failures = $this->connectFailures;
			$this->connectFailures = 0;
			throw new Exception\RuntimeException("Connection to Redis failed after $failures failures.");
		}

		$this->connectFailures = 0;
		$this->connected = TRUE;

		// Set read timeout
		if ($this->readTimeout) {
			$this->setReadTimeout($this->readTimeout);
		}

		return $this;
	}

	/**
	 * Set the read timeout for the connection. If falsey, a timeout will not be set. Negative values not supported.
	 *
	 * @param $timeout
	 * @throws Exception\InvalidArgumentException
	 * @return $this
	 */
	public function setReadTimeout($timeout)
	{
		if ($timeout < 0) {
			throw new Exception\InvalidArgumentException('Negative read timeout values are not supported.');
		}
		$this->readTimeout = $timeout;
		if ($this->connected) {
			stream_set_timeout($this->redis, (int)floor($timeout), ($timeout - floor($timeout)) * 1000000);
		}
		return $this;
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	public function auth($password)
	{
		$this->authPassword = $password;
		$response = $this->__call('auth', array($this->authPassword));
		return $response;
	}

	/**
	 * @param int $index
	 * @return bool
	 */
	public function select($index)
	{
		$this->selectedDb = (int)$index;
		$response = $this->__call('select', array($this->selectedDb));
		return $response;
	}

	/**
	 * @return bool
	 */
	public function close()
	{
		$result = TRUE;
		if ($this->connected && !$this->persistent) {
			try {
				$result = fclose($this->redis);
				$this->connected = FALSE;
			} catch (\Exception $e) {
			}
		}
		return $result;
	}

	public function setOption($key, $value)
	{

	}

	public function getOption($key)
	{

	}

	public function ping()
	{
		$this->__call('ping', array());
	}

	public function __call($name, $args)
	{
		// Lazy connection
		$this->connect();

		$name = strtolower($name);
		// Flatten arguments
		$argsFlat = NULL;
		foreach ($args as $index => $arg) {
			if (is_array($arg)) {
				if ($argsFlat === NULL) {
					$argsFlat = array_slice($args, 0, $index);
				}
				if ($name == 'mset' || $name == 'msetnx' || $name == 'hmset') {
					foreach ($arg as $key => $value) {
						$argsFlat[] = $key;
						$argsFlat[] = $value;
					}
				} else {
					$argsFlat = array_merge($argsFlat, $arg);
				}
			} else {
				if ($argsFlat !== NULL) {
					$argsFlat[] = $arg;
				}
			}
		}
		if ($argsFlat !== NULL) {
			$args = $argsFlat;
			$argsFlat = NULL;
		}

		// In pipeline mode
		if ($this->usePipeline) {
			if ($name == 'pipeline') {
				throw new Exception\RuntimeException('A pipeline is already in use and only one pipeline is supported.');
			} else {
				if ($name == 'exec') {
					if ($this->isMulti) {
						$this->commandNames[] = $name;
						$this->commands .= self::_prepare_command(array($name));
					}

					// Write request
					if ($this->commands) {
						$this->write_command($this->commands);
					}
					$this->commands = NULL;

					// Read response
					$response = array();
					foreach ($this->commandNames as $command) {
						$response[] = $this->read_reply($command);
					}
					$this->commandNames = NULL;

					if ($this->isMulti) {
						$response = array_pop($response);
					}
					$this->usePipeline = $this->isMulti = FALSE;
					return $response;
				} else {
					if ($name == 'multi') {
						$this->isMulti = TRUE;
					}
					array_unshift($args, $name);
					$this->commandNames[] = $name;
					$this->commands .= self::_prepare_command($args);
					return $this;
				}
			}
		}

		// Start pipeline mode
		if ($name == 'pipeline') {
			$this->usePipeline = TRUE;
			$this->commandNames = array();
			$this->commands = '';
			return $this;
		}

		// If unwatching, allow reconnect with no error thrown
		if ($name == 'unwatch') {
			$this->isWatching = FALSE;
		}

		// Non-pipeline mode
		array_unshift($args, $name);
		$command = self::_prepare_command($args);
		$this->write_command($command);
		$response = $this->read_reply($name);

		// Watch mode disables reconnect so error is thrown
		if ($name == 'watch') {
			$this->isWatching = TRUE;
		} // Transaction mode
		else {
			if ($this->isMulti && ($name == 'exec' || $name == 'discard')) {
				$this->isMulti = FALSE;
			} // Started transaction
			else {
				if ($this->isMulti || $name == 'multi') {
					$this->isMulti = TRUE;
					$response = $this;
				}
			}
		}

		return $response;
	}

	protected function write_command($command)
	{
		// Reconnect on lost connection (Redis server "timeout" exceeded since last command)
		if (feof($this->redis)) {
			$this->close();
			// If a watch or transaction was in progress and connection was lost, throw error rather than reconnect
			// since transaction/watch state will be lost.
			if (($this->isMulti && !$this->usePipeline) || $this->isWatching) {
				$this->isMulti = $this->isWatching = FALSE;
				throw new Exception\RuntimeException('Lost connection to Redis server during watch or transaction.');
			}
			$this->connected = FALSE;
			$this->connect();
			if ($this->authPassword) {
				$this->auth($this->authPassword);
			}
			if ($this->selectedDb != 0) {
				$this->select($this->selectedDb);
			}
		}

		$commandLen = strlen($command);
		for ($written = 0; $written < $commandLen; $written += $fwrite) {
			$fwrite = fwrite($this->redis, substr($command, $written));
			if ($fwrite === FALSE) {
				throw new Exception\RuntimeException('Failed to write entire command to stream');
			}
		}
	}

	protected function read_reply($name = '')
	{
		$reply = fgets($this->redis);
		if ($reply === FALSE) {
			throw new Exception\RuntimeException('Lost connection to Redis server.');
		}
		$reply = rtrim($reply, self::CRLF);
		#echo "> $name: $reply\n";
		$replyType = substr($reply, 0, 1);
		switch ($replyType) {
			/* Error reply */
			case '-':
				if ($this->isMulti || $this->usePipeline) {
					$response = FALSE;
				} else {
					throw new Exception\RuntimeException(substr($reply, 4));
				}
				break;
			/* Inline reply */
			case '+':
				$response = substr($reply, 1);
				if ($response == 'OK' || $response == 'QUEUED') {
					return TRUE;
				}
				break;
			/* Bulk reply */
			case '$':
				if ($reply == '$-1') {
					return FALSE;
				}
				$size = (int)substr($reply, 1);
				$response = stream_get_contents($this->redis, $size + 2);
				if (!$response) {
					throw new Exception\RuntimeException('Error reading reply.');
				}
				$response = substr($response, 0, $size);
				break;
			/* Multi-bulk reply */
			case '*':
				$count = substr($reply, 1);
				if ($count == '-1') {
					return FALSE;
				}

				$response = array();
				for ($i = 0; $i < $count; $i++) {
					$response[] = $this->read_reply();
				}
				break;
			/* Integer reply */
			case ':':
				$response = intval(substr($reply, 1));
				break;
			default:
				throw new Exception\RuntimeException('Invalid response: ' . print_r($reply, TRUE));
				break;
		}

		// Smooth over differences between phpredis and standalone response
		switch ($name) {
			case '': // Minor optimization for multi-bulk replies
				break;
			case 'config':
			case 'hgetall':
				$keys = $values = array();
				while ($response) {
					$keys[] = array_shift($response);
					$values[] = array_shift($response);
				}
				$response = count($keys) ? array_combine($keys, $values) : array();
				break;
			case 'info':
				$lines = explode(self::CRLF, trim($response, self::CRLF));
				$response = array();
				foreach ($lines as $line) {
					if (!$line || substr($line, 0, 1) == '#') {
						continue;
					}
					list($key, $value) = explode(':', $line, 2);
					$response[$key] = $value;
				}
				break;
			case 'ttl':
				if ($response === -1) {
					$response = FALSE;
				}
				break;
		}

		return $response;
	}

	/**
	 * Build the Redis unified protocol command
	 *
	 * @param array $args
	 * @return string
	 */
	private static function _prepare_command($args)
	{
		return sprintf(
			'*%d%s%s%s', count($args), self::CRLF,
			implode(array_map(array('self', '_map'), $args), self::CRLF), self::CRLF
		);
	}

	private static function _map($arg)
	{
		return sprintf('$%d%s%s', strlen($arg), self::CRLF, $arg);
	}

}
