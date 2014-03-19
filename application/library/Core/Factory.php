<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Core;

use Yaf\Application;
use Memcached as MemcachedResource;
use MemcachedException;
use Db\Adapter\Adapter;
use Db\Adapter\AdapterPool;
use Db\Table\Table;
use Db\Sql\Sql;
use Yaf\Registry;
use Yaf\Config\Simple;

abstract class Factory
{
    static protected $memcached;

    static public function memcached($persistent_id = NULL, $config = array())
    {
        if (self::$memcached && self::$memcached instanceof MemcachedResource) {
            return self::$memcached;
        } else {

            if (!empty($config)){
            } else if (Registry::get("config") && (Registry::get("config")->memcached_config instanceof Simple)
                && ($config = Registry::get("config")->memcached_config->toArray())){
            } else {
                if (isset(Application::app()->getConfig()->application->memcached)) {
                    $config = Application::app()->getConfig()->application->memcached->toArray();
                }
            }

            if ($persistent_id) {
                self::$memcached = new MemcachedResource((string) $persistent_id);
            } else {
                self::$memcached = new MemcachedResource();
            }

            $look_host = false;
            if (array_key_exists(0, $config)) {
                self::$memcached->addServers($config);
                $look_host = true;
            } else {
                if ($config['host']) {
                    self::$memcached->addServer(
                        $config['host'],
                        isset($config['port']) ? (int) $config['port'] : 11211,
                        isset($config['weight']) ? (int) $config['weight'] : 0
                    );
                    $look_host = true;
                }
            }

            if ($look_host) {
                if (self::$memcached->getResultCode() == MemcachedResource::RES_HOST_LOOKUP_FAILURE) {
                    throw new MemcachedException("Add Server To Memcached Failed", MemcachedResource::RES_HOST_LOOKUP_FAILURE);
                }
            }

            return self::$memcached;
        }
    }

    static public function db($options = array(), $name = AdapterPool::DEFAULT_ADAPTER) {
        if (AdapterPool::has($name)) {
            return AdapterPool::get($name);
        }

        if (!empty($options)){
        } else if (Registry::get("config") && (Registry::get("config")->database_config instanceof Simple)
            && ($options = Registry::get("config")->database_config->toArray())){
        } else {
            if (isset(Application::app()->getConfig()->application->database)) {
                $options = Application::app()->getConfig()->application->database->toArray();
            }
        }


        $adapter = new Adapter($options);
        AdapterPool::register($adapter);
        return AdapterPool::get();
    }

    static public function table($table, Adapter $user_adapter = null) {
        if (is_null($user_adapter)) {
            $user_adapter = self::db();
        }
        $table =  new Table(array('table'=> $table, $user_adapter));
        return $table;
    }

    static public function sql(Adapter $user_adapter = null, $table = null) {
        if (is_null($user_adapter)) {
            $user_adapter = self::db();
        }
        $sql =  new Sql($user_adapter, $table);
        return $sql;
    }
}