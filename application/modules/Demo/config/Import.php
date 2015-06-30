<?php
/**
 * Yaf.app Framework
 *
 * Import.php
 *
 * 演示模块配置文件
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 * @package Demo
 */

namespace Demo;

use Cache\CachePool;

return array(
    /**
     * Redis缓存初始化回调配置
     */
    'redis' => function() {
        $storage = CachePool::factory(
            array(
                'storage'   => 'redis',
                'namespace' => 'demo:',
            )
        );
        CachePool::register($storage);
        CachePool::get()->setResource(
            array(
                'host' => '127.0.0.1',
                'port' => 6379,
            )
        );
        CachePool::get()->getResource();
        return CachePool::get();
    },
);
