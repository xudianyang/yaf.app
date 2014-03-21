<?php
/**
 * Yaf.app Framework
 *
 * Import.php
 *
 * 日志队列模块配置文件
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 * @package Log
 */

namespace Log;

return array(
    'sql' => <<<EOT
CREATE TABLE IF NOT EXISTS `app_error_log` (
  `logid` int(10) NOT NULL AUTO_INCREMENT,
  `host` char(50) DEFAULT NULL,
  `uri` char(255) DEFAULT NULL,
  `query` char(255) DEFAULT NULL,
  `module` char(50) DEFAULT NULL,
  `controller` char(255) DEFAULT NULL,
  `action` char(50) DEFAULT NULL,
  `params` text,
  `exception` char(255) DEFAULT NULL,
  `code` int(4) DEFAULT NULL,
  `message` varchar(1000) DEFAULT NULL,
  `file` varchar(500) DEFAULT NULL,
  `line` int(4) DEFAULT NULL,
  `timestamp` int(10) DEFAULT NULL,
  `datetime` char(25) DEFAULT NULL,
  PRIMARY KEY (`logid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
EOT
    ,
);
