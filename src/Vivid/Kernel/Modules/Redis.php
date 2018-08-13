<?php
/**
 * This file contains the init class for Redis.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Predis\Client;

/**
 * Class Redis
 *
 * Redis module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Redis implements ModuleInterface
{
    /** @var Client|\Redis redis client */
    protected $redis_client;

    /**
     * Module init
     */
    public function loadModule()
    {
       // Init redis
        if (Charm::Config()->get('connections:redis.enabled', true)) {
            $host = Charm::Config()->get('connections:redis.host', '127.0.0.1');
            $port = Charm::Config()->get('connections:redis.port', 6379);
            $pw = Charm::Config()->get('connections:redis.password');
            $persistent = Charm::Config()->get('connections:redis.persistent', false);

            if(class_exists("\\Redis")) {
                // Use native redis driver
                $redis = new \Redis();

                if($persistent) {
                    $redis->pconnect($host, $port);
                } else {
                    $redis->connect($host, $port);
                }

                if(!empty($pw)) {
                    $redis->auth($pw);
                }

                // Auto serialize
                $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

                // Set prefix
                $redis->setOption(\Redis::OPT_PREFIX, Charm::Config()->get('connections:redis.prefix'));

                $this->redis_client = $redis;
                return true;
            }

            // Use Predis
            $options = [];

            // Set prefix
            $options['prefix'] = Charm::Config()->get('connections:redis.prefix');

            // Set redis password
            if (!empty($pw)) {
                $options['parameters'] = [
                    'password' => $pw
                ];
            }

            // Create client
            $this->redis_client = new Client([
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
                'persistent' => $persistent
            ], $options);

            return true;
        }

        return true;
    }

    /**
     * Get the redis client
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->redis_client;
    }

    /**
     * Remove specified keys
     *
     * @param array $keys An array of keys
     * @return int Number of keys deleted
     */
    public function del($keys)
    {
        if(!is_array($keys)) {
            $keys = [$keys];
        }

        if(class_exists("\\Redis") && $this->redis_client instanceof \Redis) {
            // phpredis
            return $this->redis_client->delete($keys);
        }

        // predis
        return $this->redis_client->del($keys);

    }

    /**
     * Magic call method
     *
     * Providing an easy interface to the redis instance.
     *
     * @param string $name      method name
     * @param array  $arguments the arguments
     *
     * @return mixed|false the return value of the called method or false if not found
     */
    public function __call($name, $arguments)
    {
        if(method_exists($this->redis_client, $name)) {
            return $this->redis_client->$name($arguments);
        }

        return false;
    }

}