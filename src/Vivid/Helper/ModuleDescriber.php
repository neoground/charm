<?php
/**
 * This file contains the ModuleDescriber class
 */

namespace Charm\Vivid\Helper;

/**
 * Class ModuleDescriber
 *
 * Describe a module / something inside a module
 *
 * This is used for e.g. the AppStorage, where you
 * want to add a reference to a method inside a module.
 *
 * @package Charm\Vivid\Helper
 */
class ModuleDescriber
{
    /** @var string module */
    protected $module;

    /** @var string method */
    protected $method;

    /**
     * ModuleDescriber factory
     *
     * @return self
     */
    public static function create()
    {
        return new self;
    }

    /**
     * Get the module
     *
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set the module
     *
     * @param string $module
     *
     * @return ModuleDescriber
     */
    public function setModule($module)
    {
        $this->module = $module;
        return $this;
    }

    /**
     * Get the method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the method
     *
     * @param string $method
     *
     * @return ModuleDescriber
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }
}