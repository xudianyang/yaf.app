<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Output;

use Sender\SenderInterface;

interface OutputInterface
{
	/**
	 * Output the content
	 */
	public function __invoke(SenderInterface $sender);

}