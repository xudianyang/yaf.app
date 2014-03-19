<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Output;

use Sender\SenderInterface;

class FormatOutput implements OutputInterface
{
	/**
	 * The data to output
	 *
	 * @var mixed
	 */
	protected $data;

	/**
	 * The format type to output
	 *
	 * @var string json|xml|serialize|plain
	 */
	protected $format = 'json';

	public function __construct($data = null)
	{
		if ($data !== null) {
			$this->setData($data);
		}
	}

	/**
	 * Set the data to output
	 *
	 * @param mixed $data
	 * @return $this
	 */
	public function setData($data)
	{
		$this->data = $data;
		return $this;
	}

	/**
	 * Set the data output format type
	 *
	 * @param string $format
	 * @return  $this
	 */
	public function setFormat($format)
	{
		$this->format = strtolower($format);
		return $this;
	}

	/**
	 * Output the content
	 */
	public function __invoke(SenderInterface $sender)
	{
		$data = $this->data;
		if ($data !== null) {
			if (method_exists($data, 'toArray')) {
				$data = $data->toArray();
			} elseif ($data instanceof \Traversable) {
				$temp = array();
				foreach ($data as $key => $val) {
					$temp[$key] = $val;
				}
				$data = $temp;
			}
			switch ($this->format) {
				case 'serialize':
					$data = "serialize\r\n\r\n".serialize($data);
					break;
				case 'plain':
					$data = "plain\r\n\r\n".print_r($data, true);
					break;
				case 'json': default:
					$data = "json\r\n\r\n".json_encode($data);
					break;
			}
		}

		$sender->setContent($data);
        $sender->send();
	}
}