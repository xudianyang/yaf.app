<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Protocol\Ftp;

class Client
{
    const MOD_DEFAULT = 0777;

    protected $messages = array();

    protected static $defaultOptions = array(
        'server' => null,
        'port' => 21,
        'anonymous' => false,
        'username' => '',
        'password' => '',
        'passive' => true,
        'ssl' => false,
        'connect_timeout' => 15,
        'timeout' => 30,
        'chmod' => false
    );

    protected $options = array();

    protected $connection = null;

    protected $currentDir = '/';

    public function __construct(array $options = array())
    {
        if (!function_exists('ftp_connect')) {
            throw new Exception\RuntimeException('FTP extension not loaded or is disabled');
        }

        $this->options = array_merge(self::$defaultOptions, $options);
    }

    /**
     * 获取 FTP 连接
     *
     * @return null
     * @throws Exception\RuntimeException
     */
    public function getConnection()
    {
        if ($this->connection === null) {
            $server = $this->options['server'];
            $port = (int)$this->options['port'] ?: 21;
            $anonymous = (bool)$this->options['anonymous'];
            $username = trim($this->options['username']);
            $password = $this->options['password'];
            $passive = (bool)$this->options['passive'];
            $ssl = (bool)$this->options['ssl'];
            $connect_timeout = (int)$this->options['connect_timeout'] ?: 90;
            $timeout = (int)$this->options['timeout'] ?: 90;

            $handler = $ssl && function_exists('ftp_ssl_connect') ? 'ftp_ssl_connect' : 'ftp_connect';
            $connection = $handler($server, $port, $connect_timeout);
            if ($connection === false) {
                throw new Exception\InvalidArgumentException(sprintf('Connect to server %s at port %s failed', $server, $port));
            }
            $this->logMessage(sprintf('Connect to %s:%s: OK', $server, $port));

            try {
                if ($anonymous) {
                    $username = 'anonymous';
                    $ret = @ftp_login($connection, $username, '');
                    if ($ret === false) {
                        $error = error_get_last();
                        throw new Exception\InvalidArgumentException(sprintf('User %s auth failed with error: %s', $username, $error['message']));
                    }
                    $this->logMessage(sprintf('USER %s: OK', $username));
                } else {
                    $ret = @ftp_login($connection, $username, $password);
                    if ($ret === false) {
                        $error = error_get_last();
                        throw new Exception\InvalidArgumentException(sprintf('User %s auth failed using password with error: %s', $username, $error['message']));
                    }
                    $this->logMessage(sprintf('USER %s: OK', $username));
                    $this->logMessage(sprintf('PASS %s: OK', str_repeat('*', mt_rand(8, 10))));
                }

                $ret = ftp_pasv($connection, $passive);
                if ($ret === false) {
                    throw new Exception\RuntimeException(sprintf('Turns passive mode %s failed', $passive ? 'on' : 'off'));
                }
                $this->logMessage(sprintf('Turns passive mode %s: OK', $passive ? 'on' : 'off'));

                $this->setOption(FTP_TIMEOUT_SEC, $timeout, $connection);
            } catch (Exception\ExceptionInterface $ex) {
                ftp_close($connection);
                throw $ex;
            }

            $this->connection = $connection;
        }

        return $this->connection;
    }

    public function setOption($name, $value, $connection = null)
    {
        $value = (int)$value;

        if ($connection === null) {
            $connection = $this->getConnection();
        }

        $ret = @ftp_set_option($connection, $name, $value);
        if ($ret === false) {
            throw new Exception\RuntimeException(sprintf('Set option %s to %d failed', $name, $value));
        }
        //$this->logMessage(sprintf('SET OPTION %s to %d: OK', $name, $value));
        return $ret;
    }

    /**
     * 创建目录
     *
     * @param $directory
     * @param int $mod
     * @param bool $return_pwd
     * @return bool|string
     * @throws Exception\RuntimeException
     */
    public function mkdir($directory, $mod = self::MOD_DEFAULT, $return_pwd = true)
    {
        $directory = $this->normalize($directory);

        if ($return_pwd) {
            $current_dir = $this->pwd();
        }

        $path = '';
        $connection = $this->getConnection();

        $parts = explode('/', $directory);
        foreach ($parts as $part) {
            if ($part === '') {
                $path = '/';
                continue;
            }

            $path = $path . $part . '/';
            try {
                $this->chdir($path);
            } catch (Exception\RuntimeException $ex) {
                $ret = @ftp_mkdir($connection, $path);
                if ($ret === false) {
                    $error = error_get_last();
                    throw new Exception\RuntimeException(sprintf('MKDIR %s failed with error: %s', $path, $error['message']));
                }
                $this->logMessage(sprintf('MKDIR %s: OK', $path));
                $this->options['chmod'] && $this->chmod($path, $mod);
            }
        }

        if ($return_pwd && !empty($current_dir)) {
            $this->chdir($current_dir);
        }

        return empty($ret) ? false : $ret;
    }

    /**
     * 删除目录
     *
     * @param $directory
     * @return bool
     * @throws Exception\RuntimeException
     */
    public function rmdir($directory)
    {
        $directory = $this->normalize($directory);
        $ret = ftp_rmdir($this->getConnection(), $directory);
        if ($ret === false) {
            throw new Exception\RuntimeException(sprintf('RMDIR %s failed', $directory));
        }
        $this->logMessage(sprintf('RMDIR %s: OK', $directory));
        return $ret;
    }

    /**
     * 变更目录
     *
     * @param $directory
     * @return bool
     * @throws Exception\RuntimeException
     */
    public function chdir($directory)
    {
        $directory = $this->normalize($directory);

        if (strlen($directory) > 1) {
            $directory = rtrim($directory, '/\\');
        }

        if ($directory === $this->currentDir) {
            return true;
        }

        $ret = @ftp_chdir($this->getConnection(), $directory);
        if ($ret === false) {
            $error = error_get_last();
            throw new Exception\RuntimeException(sprintf('CHDIR %s failed with error: %s', $directory, $error['message']));
        }
        $this->currentDir = $directory;
        $this->logMessage(sprintf('CHDIR %s: OK', $directory));
        return $ret;
    }

    /**
     * 获取当前目录
     *
     * @return string
     * @throws Exception\RuntimeException
     */
    public function pwd()
    {
        $ret = ftp_pwd($this->getConnection());
        if ($ret === false) {
            throw new Exception\RuntimeException('PWD failed');
        }
        $this->logMessage(sprintf('PWD: %s', $ret));
        return $ret;
    }

    /**
     * 列出目录
     *
     * @param $directory
     * @param string $params
     * @return array
     * @throws Exception\RuntimeException
     */
    public function lsdir($directory, $params = '-la')
    {
        $directory = $this->normalize($directory);
        $ret = ftp_nlist($this->getConnection(), sprintf('%s %s', $params, $directory));
        if ($ret === false) {
            throw new Exception\RuntimeException(sprintf('NLIST %s %s failed', $params, $directory));
        }
        $this->logMessage(sprintf('NLIST %s %s: OK', $params, $directory));
        return $ret;
    }

    /**
     * 下载文件
     *
     * @param $remote_file
     * @param $local_file
     * @param int $mode
     * @param bool $resume
     * @return bool
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    public function get($remote_file, $local_file, $mode = FTP_BINARY, $resume = false)
    {
        $remote_file = $this->normalize($remote_file);
        $local_file = $this->normalize($local_file);

        $local_folder = dirname($local_file);
        if (!is_dir($local_folder)) {
            throw new Exception\InvalidArgumentException(sprintf('Local folder %s not exists', $local_folder));
        }

        if ($resume === true && is_file($local_file)) {
            $resumepos = filesize($local_file);
        } else {
            $resumepos = (int)$resume;
        }

        $ret = @ftp_get($this->getConnection(), $local_file, $remote_file, (int)$mode, $resumepos);
        if ($ret === false) {
            $error = error_get_last();
            throw new Exception\RuntimeException(sprintf('Get remote file %s failed with error: %s', $remote_file, $error['message']));
        }
        $this->logMessage(sprintf('GET %s: %s', $remote_file, $local_file));
        return $ret;
    }

    /**
     * 下载文件到本地文件句柄
     *
     * @param $remote_file
     * @param $handle
     * @param int $mode
     * @param bool $resume
     * @return bool
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    public function fget($remote_file, $handle, $mode = FTP_BINARY, $resume = false)
    {
        $remote_file = $this->normalize($remote_file);

        if (!is_resource($handle)) {
            throw new Exception\InvalidArgumentException('$handle is not a valid resource');
        }

        if ($resume === true) {
            $stat = fstat($handle);
            $resumepos = $stat['size'];
        } else {
            $resumepos = (int)$resume;
        }

        $ret = @ftp_fget($this->getConnection(), $handle, $remote_file, (int)$mode, $resumepos);
        if ($ret === false) {
            $error = error_get_last();
            throw new Exception\RuntimeException(sprintf('Get remote file %s failed with error: %s', $remote_file, $error['message']));
        }
        $this->logMessage(sprintf('FGET %s: OK', $remote_file));
        return $ret;
    }

    /**
     * 上传文件
     *
     * @param $local_file
     * @param $remote_file
     * @param int $mode
     * @param int $startpos
     * @return bool
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    public function put($local_file, $remote_file, $mode = FTP_BINARY, $startpos = 0)
    {
        $local_file = $this->normalize($local_file);
        $remote_file = $this->normalize($remote_file);

        if (!is_file($local_file) || !is_readable($local_file)) {
            throw new Exception\InvalidArgumentException(sprintf('%s is not exist or is not readable', $local_file));
        }

        $ret = @ftp_put($this->getConnection(), $remote_file, $local_file, (int)$mode, (int)$startpos);
        if ($ret === false) {
            $error = error_get_last();
            throw new Exception\RuntimeException(sprintf('Put local file %s to remote file %s failed with error: %s', $local_file, $remote_file, $error['message']));
        }
        $this->logMessage(sprintf('PUT %s to %s: OK', $local_file, $remote_file));
        return $ret;
    }

    /**
     * 上次本地文件句柄
     *
     * @param $handle
     * @param $remote_file
     * @param int $mode
     * @param int $startpos
     * @return bool
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    public function fput($handle, $remote_file, $mode = FTP_BINARY, $startpos = 0)
    {
        if (!is_resource($handle)) {
            throw new Exception\InvalidArgumentException('$handle is not a valid resource');
        }
        $remote_file = $this->normalize($remote_file);

        $ret = @ftp_fput($this->getConnection(), $remote_file, $handle, $mode, $startpos);
        if ($ret === false) {
            $error = error_get_last();
            throw new Exception\RuntimeException(sprintf('Put local file [resource] to remote file %s failed with error: %s', $remote_file, $error['message']));
        }
        $this->logMessage(sprintf('FPUT resource to %s: OK', $remote_file));
        return $ret;
    }

    /**
     * 上传本地文件到远程
     *
     * @param $local_file
     * @param $remote_file
     * @param bool $return_pwd
     * @return bool
     */
    public function upload($local_file, $remote_file, $return_pwd = true)
    {
        if ($return_pwd) {
            $current_remote_dir = $this->pwd();
        }

        $pathinfo = pathinfo($remote_file);
        $remote_dir = $pathinfo['dirname'];
        $remote_name = $pathinfo['basename'];

        try {
            $this->chdir($remote_dir);
        } catch (Exception\RuntimeException $ex) {
            $this->mkdir($remote_dir, self::MOD_DEFAULT, false);
            $this->chdir($remote_dir);
        }

        if (is_file($local_file)) {
            $ret = $this->put($local_file, $remote_name);
        } else {
            $ret = true;
        }

        if ($return_pwd && !empty($current_remote_dir)) {
            $this->chdir($current_remote_dir);
        }

        return $ret;
    }

    /**
     * 删除文件
     *
     * @param $remote_path
     * @return bool
     * @throws Exception\RuntimeException
     */
    public function delete($remote_path)
    {
        $remote_path = $this->normalize($remote_path);
        $ret = ftp_delete($this->getConnection(), $remote_path);
        if ($ret === false) {
            throw new Exception\RuntimeException(sprintf('DELETE %s failed', $remote_path));
        }
        $this->logMessage(sprintf('DELETE %s: OK', $remote_path, $ret));
        return $ret;
    }

    /**
     * 重命名
     *
     * @param $oldname
     * @param $newname
     * @return bool
     * @throws Exception\RuntimeException
     */
    public function rename($oldname, $newname)
    {
        $oldname = $this->normalize($oldname);
        $newname = $this->normalize($newname);
        $ret = ftp_rename($this->getConnection(), $oldname, $newname);
        if ($ret === false) {
            throw new Exception\RuntimeException(sprintf('Rename %s to %s failed', $oldname, $newname));
        }
        $this->logMessage(sprintf('Rename %s to %s: OK', $oldname, $newname));
        return $ret;
    }

    /**
     * 获取指定文件或路径的大小
     *
     * @param $remote_path
     * @return int
     * @throws Exception\RuntimeException
     */
    public function size($remote_path)
    {
        $remote_path = $this->normalize($remote_path);
        $ret = ftp_size($this->getConnection(), $remote_path);
        if ($ret === -1) {
            throw new Exception\RuntimeException(sprintf('SIZE %s failed', $remote_path));
        }
        $this->logMessage(sprintf('SIZE %s: %s', $remote_path, $ret));
        return $ret;
    }

    /**
     * 在服务器上执行 SITE 命令
     *
     * @param $command
     * @return bool
     * @throws Exception\RuntimeException
     */
    public function site($command)
    {
        $command = $this->normalize($command);
        $ret = ftp_site($this->getConnection(), $command);
        if ($ret === false) {
            throw new Exception\RuntimeException(sprintf('SITE command %s failed', $command));
        }
        $this->logMessage(sprintf('SITE %s: OK', $command));
        return $ret;
    }

    /**
     * 改变文件或目录的权限
     *
     * @param $remote_path
     * @param int $mod
     * @return bool|int
     * @throws Exception\RuntimeException
     */
    public function chmod($remote_path, $mod = self::MOD_DEFAULT)
    {
        $remote_path = $this->normalize($remote_path);
        if (function_exists('ftp_chmod')) {
            $ret = ftp_chmod($this->getConnection(), $mod, $remote_path);
        } else {
            $ret = ftp_site($this->getConnection(), 'CHMOD ' . $mod . ' ' . $remote_path);
        }
        if ($ret === false) {
            throw new Exception\RuntimeException(sprintf('chmod %s to %d failed', $remote_path, $mod));
        }
        $this->logMessage(sprintf('CHMOD %s to %d: OK', $remote_path, $mod));
        return $ret;
    }

    /**
     * 获取消息
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * 清空消息
     */
    public function clearMessages()
    {
        $this->messages = array();
    }

    /**
     * 关闭连接
     */
    public function close()
    {
        if ($this->connection) {
            ftp_close($this->connection);
            $this->logMessage(sprintf('Connection closed'));
        }
    }

    public function __destruct()
    {
        $this->close();
        $this->clearMessages();
    }

    protected function logMessage($message)
    {
        $this->messages[] = sprintf('[%s] %s', date('Y-m-d H:i:s'), $message);
    }

    protected function normalize($name)
    {
        $name = str_replace(array('..'), '', trim($name));
        $name = str_replace(array('//', '\\\\'), '/', $name);
        return $name;
    }
}