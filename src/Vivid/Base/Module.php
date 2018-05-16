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

    /**
     * Get the reflection class of the extended class
     *
     * @return \ReflectionClass
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
     */
    public function getBaseDirectory()
    {
        return dirname($this->getReflectionClass()->getFileName());
    }
}