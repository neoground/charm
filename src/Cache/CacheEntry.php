<?php
/**
 * This file contains the cache entry class
 */

namespace Charm\Cache;

/**
 * Class CacheEntry
 *
 * Cache entry element
 *
 * @package Charm\Cache
 */
class CacheEntry
{
    /** @var string the key */
    protected $key;

    /** @var mixed the value */
    protected $val;

    /** @var array the tags */
    protected $tags = [];

    /**
     * CacheEntry constructor.
     *
     * @param string $key cache key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Set the value
     *
     * @param mixed $val the callback / object / array / string
     *
     * @return $this
     */
    public function setValue($val)
    {
        $data = $val;
        if ($data instanceof \Closure) {
            $data = $val();
        }

        if(is_object($data)) {
            $data = serialize($data);
        }

        if(is_array($data)) {
            $data = json_encode($data);
        }

        $this->val = $data;

        return $this;
    }

    /**
     * Set one or multiple tags
     *
     * @param array|string $tags tag(s)
     *
     * @return $this
     */
    public function setTags($tags)
    {
        if(!is_array($tags)) {
            $tags = [$tags];
        }

        $this->tags = array_merge($this->tags, $tags);
        return $this;
    }

    /**
     * Set the cache key
     *
     * @param string $key the cache key
     *
     * @return $this;
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Get the stored value
     *
     * @return mixed
     */
    public function getValue()
    {
        // Format output
        if(is_serialized($this->val)) {
            // Handling serialized
            $this->val = unserialize($this->val);
        } elseif(!is_array($this->val) && is_json($this->val)) {
            // Handling JSON
            $this->val = json_decode($this->val, true);
        }

        return $this->val;
    }

    /**
     * Get the tags
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Get the key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Magic toString method
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode([
            'key' => $this->key,
            'tags' => $this->tags,
            'value' => $this->val
        ]);
    }

    /**
     * CacheEntry factory for stored entries
     *
     * @param string $data the json encoded data from the cache
     *
     * @return CacheEntry
     */
    public static function make($data)
    {
        $x = json_decode($data, true);

        $entry = new self($x['key']);
        $entry->setValue($x['value'])
            ->setTags($x['tags']);

        return $entry;
    }

}