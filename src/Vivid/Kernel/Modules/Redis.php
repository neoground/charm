<?php
/**
 * This file contains the init class for Redis.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Predis\Client;

/**
 * Class Redis
 *
 * Redis module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Redis extends Module implements ModuleInterface
{
    /** @var Client|\Redis redis client */
    protected $redis_client;

    /** @var Client|\Redis custom redis client for getPredisClient */
    protected $custom_client;

    /**
     * Module init
     */
    public function loadModule()
    {
        // Init redis
        if (C::Config()->get('connections:redis.enabled', true)) {
            $host = C::Config()->get('connections:redis.host', '127.0.0.1');
            $port = C::Config()->get('connections:redis.port', 6379);
            $pw = C::Config()->get('connections:redis.password');
            $persistent = C::Config()->get('connections:redis.persistent', false);
            $driver = strtolower(C::Config()->get('connections:redis.driver', 'phpredis'));

            // Prevent socket timeout
            ini_set("default_socket_timeout", -1);

            if ($driver == 'phpredis' && class_exists("\\Redis")) {
                // Use native redis driver
                $redis = new \Redis();

                if ($persistent) {
                    $redis->pconnect($host, $port);
                } else {
                    $redis->connect($host, $port);
                }

                // Auto serialize
                $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

                // Set prefix
                $redis->setOption(\Redis::OPT_PREFIX, C::Config()->get('connections:redis.prefix'));

                // Set timeout
                $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);

                if (!empty($pw)) {
                    $redis->auth($pw);
                }

                $this->redis_client = $redis;
                return true;
            }

            // Use Predis
            $options = [];

            // Set prefix
            $options['prefix'] = C::Config()->get('connections:redis.prefix');

            // Set redis password
            if (!empty($pw)) {
                $options['parameters'] = [
                    'password' => $pw,
                ];
            }

            // Create client
            $this->redis_client = new Client([
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
                'persistent' => $persistent,
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
     * Get the predis client
     *
     * @return Client
     */
    public function getPredisClient()
    {
        if ($this->redis_client instanceof Client) {
            return $this->redis_client;
        }

        if ($this->custom_client instanceof Client) {
            return $this->custom_client;
        }

        // Init client
        $host = C::Config()->get('connections:redis.host', '127.0.0.1');
        $port = C::Config()->get('connections:redis.port', 6379);
        $pw = C::Config()->get('connections:redis.password');
        $persistent = C::Config()->get('connections:redis.persistent', false);

        $options = [];

        // Set prefix
        $options['prefix'] = C::Config()->get('connections:redis.prefix');

        // Set redis password
        if (!empty($pw)) {
            $options['parameters'] = [
                'password' => $pw,
            ];
        }

        // Create client
        $this->custom_client = new Client([
            'scheme' => 'tcp',
            'host' => $host,
            'port' => $port,
            'persistent' => $persistent,
        ], $options);

        return $this->custom_client;
    }

    /**
     * Remove specified keys
     *
     * @param array|string $keys An array of keys or a single string key
     *
     * @return int Number of keys deleted
     */
    public function del($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        if (extension_loaded('redis') && $this->redis_client instanceof \Redis) {
            // phpredis
            return $this->redis_client->delete($keys);
        }

        // predis
        return $this->redis_client->del($keys);

    }

    /**
     * Remove specified keys from a hash
     *
     * @param string $key    the hash key
     * @param array  $values an array of values
     *
     * @return int Number of keys deleted
     */
    public function hdel($key, $values)
    {
        if (!is_array($values)) {
            $values = [$values];
        }

        if (extension_loaded('redis') && $this->redis_client instanceof \Redis) {
            // phpredis
            $ret = true;
            foreach ($values as $val) {
                $ret &= $this->redis_client->hDel($key, $val);
            }
            return $ret;
        }

        // predis
        return $this->redis_client->hdel($key, $values);

    }

    /**
     * Adds the string values to the tail (right) of the list. Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned.
     *
     * @param string       $key
     * @param string|array $value
     *
     * @return int|bool     The new length of the list in case of success, FALSE in case of Failure.
     */
    public function rpush($key, $value)
    {
        if (extension_loaded('redis') && $this->redis_client instanceof \Redis) {
            // phpredis

            if (is_array($value)) {
                $ret = 0;
                foreach ($value as $val) {
                    $ret = $this->redis_client->rpush($key, $val);
                }
                return $ret;
            }

            return $this->redis_client->rpush($key, $value);
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        // predis
        return $this->redis_client->rpush($key, $value);
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
        if (method_exists($this->redis_client, $name)) {
            if (count($arguments) == 1) {
                return $this->redis_client->$name($arguments[0]);
            } else {
                return call_user_func($this->redis_client->$name, $arguments);
            }
        }

        return false;
    }

}