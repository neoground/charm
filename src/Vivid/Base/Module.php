<?php
/**
 * This file contains the Module class.
 */

namespace Charm\Vivid\Base;


/**
 * Class Module
 *
 * Base module class
 *
 * @package Charm\Vivid\Base
 */
class Module
{
    /** @var \ReflectionClass reflection class of this class */
    protected $reflection;

    /** @var array module settings */
    protected $settings = [];

    /**
     * Get the reflection class of the extended class
     *
     * @return \ReflectionClass
     *
     * @throws \ReflectionException
     */
    public function getReflectionClass()
    {
        if(!is_object($this->reflection)) {
            $this->reflection = new \ReflectionClass(static::class);
        }

        return $this->reflection;
    }

    /**
     * Get base directory of this module
     *
     * @return string  the absolute path to base directory without leading slash
     *
     * @throws \ReflectionException
     */
    public function getBaseDirectory()
    {
        return dirname($this->getReflectionClass()->getFileName());
    }

    /**
     * Set a setting
     *
     * @param string $key
     * @param mixed $value
     */
    public function setSetting($key, $value)
    {
        $this->settings[$key] = $value;
    }

    /**
     * Get a setting
     *
     * @param string $key
     * @return bool|mixed the data or false if not found
     */
    public function getSetting($key)
    {
        if(array_key_exists($key, $this->settings)) {
            return $this->settings[$key];
        }

        return false;
    }

}