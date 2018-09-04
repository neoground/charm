<?php
/**
 * This file contains the init class for the cache.
 */

namespace Charm\Cache;

use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Predis\Client;

/**
 * Class Cache
 *
 * Cache module
 *
 * TODO: Add support for memcache, database, flat file + multiple cache stores
 *
 * @package Charm\Cache
 */
class Cache implements ModuleInterface
{
    /** @var string The global cache prefix */
    protected $prefix = 'ccache';

    /** @var Client the redis client */
    protected $redis;

    /**
     * Module init
     */
    public function loadModule()
    {
        $this->redis = Charm::Database()->getRedisClient();
    }

    /**
     * Remember a value
     *
     * Saves it in redis
     *
     * @param string   $key     the unique key
     * @param int      $minutes minutes of expiration
     * @param mixed    $callback the callback / or cache entry
     *
     * @return mixed the stored value
     */
    public function remember($key, $minutes, $callback)
    {
        if(!$this->has($key)) {
            if($callback instanceof CacheEntry) {
                $this->setEntry($callback, $minutes);
            } else {
                $this->set($key, $minutes, $callback);
            }
        }
        return $this->get($key);
    }

    /**
     * Set a cache entry
     *
     * @param string   $key     the unique key
     * @param int      $minutes minutes of expiration
     * @param mixed    $callback the callback / object / array / string
     *
     * @return bool true if saved, false if existing
     */
    public function set($key, $minutes, $callback)
    {
        // Prepend prefix
        $key = $this->prefix . ':' . $key;

        // Don't have it cached? Generate it!
        if(!$this->redis->exists($key)) {

            $entry = new CacheEntry($key);
            $entry->setValue($callback);

            $this->redis->set($key, (string) $entry);
            $this->redis->expire($key, $minutes * 60);
            return true;
        }

        return false;
    }

    /**
     * @param CacheEntry $entry   the cache entry object
     * @param int        $minutes minutes of expiration
     *
     * @return bool true if saved, false if existing
     */
    public function setEntry($entry, $minutes)
    {
        // Update key with prefix
        $key = $this->prefix . ':' . $entry->getKey();
        $entry->setKey($key);

        // Save in redis
        $this->redis->set($key, (string) $entry);
        $this->redis->expire($key, $minutes * 60);

        // Set tags
        $tags = $entry->getTags();
        if(!empty($tags)) {
            // Go through all tags and add them
            foreach($tags as $tag) {
                if(extension_loaded('redis') && $this->redis instanceof \Redis) {
                    $this->redis->sAdd($this->prefix . '_tags:' . $tag, $key);
                } else {
                    $this->redis->sadd($this->prefix . '_tags:' . $tag, [$key]);
                }
            }
        }

        return true;
    }

    /**
     * Get all keys of cache entries saved with this tag / these tags
     *
     * @param string|array $tag the cache tag or array of cache tags where entry must be in
     *
     * @return array
     */
    public function getByTag($tag)
    {
        if(is_array($tag)) {
            return $this->redis->sinter($tag);
        }

        return $this->redis->smembers($this->prefix . '_tags:' . $tag);
    }

    /**
     * Get a stored cache entry
     *
     * @param string     $key     the cache key
     * @param null|mixed $default (opt.) the default return value, default: null
     *
     * @return mixed|null|string returns the saved entry or $default if not found
     */
    public function get($key, $default = null)
    {
        // Prepend prefix
        $key = $this->prefix . ':' . $key;

        if(!$this->has($key)) {
            // Not existing, return default
            return $default;
        }

        // Get data
        $entry = CacheEntry::make($this->redis->get($key));
        return $entry->getValue();
    }

    /**
     * Check if a cache key exists
     *
     * @param string $key the key (prefix will be prepended if not existing)
     *
     * @return bool
     */
    public function has($key) {
        if(!in_string($this->prefix, $key)) {
            $key = $this->prefix . ':' . $key;
        }

        return (bool) $this->redis->exists($key);
    }

    /**
     * Remove a cache entry
     *
     * @param string $key the key
     */
    public function remove($key)
    {
        // If prefix is provided, no need to add it again
        if(!in_string($this->prefix, $key)) {
            $key = $this->prefix . ':' . $key;
        }

        if(extension_loaded('redis') && $this->redis instanceof \Redis) {
            // Phpredis
            $this->redis->del($key);
        } else {
            // Predis
            $this->redis->del([$key]);
        }
    }

    /**
     * Remove cache entries by a tag / by tags
     *
     * @param string|array $tag the cache tag or array of cache tags where entry must be in
     */
    public function removeByTag($tag)
    {
        $keys = $this->getByTag($tag);
        foreach($keys as $key) {
            $this->remove($key);
        }

        // Also remove tag because we removed everything in it!
        $this->remove($this->prefix . '_tags:' . $tag);
    }

}