<?php
/**
 * This file contains the EngineManager class
 */

namespace Charm\Vivid\Kernel;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;

/**
 * Class EngineManager
 *
 * @package Charm\Vivid\Kernel
 */
class EngineManager extends Module
{
    /** @var string  the environment */
    protected $environment;

    /** @var array  the config */
    protected $config;

    /**
     * Set the environment
     *
     * @param string $env
     */
    public function setEnvironment($env)
    {
        $this->environment = $env;
    }

    /**
     * Get the environment
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Set the config
     *
     * Will only update parameters provided in config array,
     * not replacing the whole array.
     *
     * @param array  $arr  the config array
     */
    public function setConfig($arr)
    {
        $this->config = Charm::Arrays()->array_merge_recursive($this->config, $arr);
    }

    /**
     * Get the config
     *
     * @param string      $key      config key
     * @param null|mixed  $default  default value to return
     *
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        return Charm::Arrays()->get($this->config, $key, $default);
    }

}