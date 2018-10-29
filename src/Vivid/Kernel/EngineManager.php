<?php
/**
 * This file contains the EngineManager class
 */

namespace Charm\Vivid\Kernel;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;
use Charm\Vivid\PathFinder;

/**
 * Class EngineManager
 *
 * Providing a base class for all app modules
 *
 * @package Charm\Vivid\Kernel
 */
class EngineManager extends Module
{
    /** @var string  the environment */
    protected $environment;

    /** @var array  the config */
    protected $config;

    /** @var bool switch if env is set via app.env file */
    protected $set_via_file = false;

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
        // Use manually set environment from the app.env file
        $appenv = PathFinder::getAppPath() . DS . 'app.env';
        if(file_exists($appenv) && !$this->set_via_file) {
            $this->environment = trim(file_get_contents($appenv));
            $this->set_via_file = true;
        }

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

    /**
     * Enable handling of preflight requests for CORS
     *
     * This will automatically handle the "OPTIONS" request and return an empty
     * body which is needed for most APIs with CORS.
     *
     * Explanation: https://developer.mozilla.org/en-US/docs/Glossary/Preflight_request
     */
    public function enablePreflightHandling()
    {
        if(Charm::Server()->get('REQUEST_METHOD') == "OPTIONS") {
            // Basic headers are provided by nginx. No Access-Control needed again...
            header("Content-Length: 0");
            header("Content-Type: text/plain");
            exit(0);
        }
    }

    /**
     * Actions after charm initialization
     *
     * This method is called after the init sequence of charm is completed
     * and before the routing system starts.
     */
    public function postInit()
    {

    }

}