<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Output;

use Sender\Http as HttpSender;
use Sender\SenderInterface;

class AttachmentOutput implements OutputInterface
{
	/**
	 * file name of attachment output
	 *
	 * @var string
	 */
	protected $fileName = null;

	/**
	 * file stream mimetype, try detect if null given
	 *
	 * @var string
	 */
	protected $mimeType = null;

	/**
	 * file of to output
	 *
	 * @var string
	 */
	protected $file = null;

	/**
	 * use X-SendFile module
	 *
	 * @var boolean
	 */
	protected $XSendFile = false;

	public function __construct($file)
	{
		$this->setFile($file);
		if (function_exists('apache_get_modules') && in_array('mod_xsendfile', apache_get_modules())) {
			$this->setXSendFile(true);
		}
	}

	/**
	 * Set the file to output
	 *
	 * @param string $file
	 * @return $this
	 * @throws Exception\RuntimeException
	 * @throws Exception\InvalidArgumentException
	 */
	public function setFile($file)
	{
		if (!is_string($file)) {
			throw new Exception\InvalidArgumentException(sprintf(
				'%s: expects an string; received "%s"', __METHOD__,
				(is_object($file) ? get_class($file) : gettype($file))
			));
		}
		if (!is_file($file) || !is_readable($file)) {
			throw new Exception\RuntimeException("Cannot access file '$file'.");
		}
		$this->file = $file;
		return $this;
	}

	/**
	 * set the mimetype of filestream
	 *
	 * @param string $mimeType
	 * @return $this
	 */
	public function setMimeType($mimeType)
	{
		$this->mimeType = $mimeType;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getMimeType()
	{
		if (null === $this->mimeType) {
			$this->mimeType = self::getMimeTypeFromFile($this->file);
		}
		return $this->mimeType;
	}

	/**
	 * set the filename to output
	 *
	 * @param string $fileName
	 * @return $this
	 */
	public function setFileName($fileName)
	{
		$this->fileName = $fileName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFileName()
	{
		if (null === $this->fileName) {
			$this->fileName = basename($this->file);
		}
		return $this->fileName;
	}

	/**
	 * set use X-SendFile module
	 *
	 * @param bool $flag
	 * @return $this
	 */
	public function setXSendFile($flag)
	{
		$this->XSendFile = (bool)$flag;
		return $this;
	}

	public function __invoke(SenderInterface $sender)
	{
		if (!$sender instanceof HttpSender) {
			throw new Exception\RuntimeException('This output must use at Sender\Http');
		}

		$fileName = $this->getFileName();

		$fileName = strstr($_SERVER["HTTP_USER_AGENT"], 'MSIE')
			? rawurlencode($fileName) : htmlspecialchars($fileName);


		$headers = $sender->getHeaders();

		$headers->addHeaderLine('Content-Type', $this->getMimeType())
			->addHeaderLine('Content-Disposition', 'attachment; filename="' . $fileName . '"')
			->addHeaderLine('Content-Length', filesize($this->file));

		if ($this->XSendFile) {
			$headers->addHeaderLine('X-Sendfile', realpath($this->file));
		} else {
			$sender->setContent(fopen($this->file, 'r'));
		}
	}

	protected static function getMimeTypeFromFile($file)
	{
		return mime_content_type($file) ? : 'application/octet-stream';
	}
}