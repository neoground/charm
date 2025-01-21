<?php
/**
 * This file contains the EngineManager class
 */

namespace Charm\Vivid\Kernel;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;

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
    protected string $environment = '';

    /** @var array  the config */
    protected array $config = [];

    /** @var bool switch if env is set via app.env file */
    protected bool $set_via_file = false;

    /**
     * Set the environment
     *
     * @param string $env
     */
    public function setEnvironment(string $env): void
    {
        $this->environment = $env;
    }

    /**
     * Get the environment
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        // Use manually set environment from the app.env file
        $appenv = C::Storage()->getAppPath() . DS . 'app.env';
        if (!$this->set_via_file && file_exists($appenv)) {
            $content = trim(file_get_contents($appenv));
            if(str_contains('=', $content)) {
                // Modern app.env config file
                $ini_array = parse_ini_string($content);
                $this->environment = $ini_array['ENVIRONMENT'];
            } else {
                // Plain environment
                $this->environment = $content;
            }

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
     * @param array $arr the config array
     */
    public function setConfig(array $arr): void
    {
        $this->config = C::Arrays()->array_merge_recursive($this->config, $arr);
    }

    /**
     * Get the config
     *
     * @param string     $key     config key
     * @param mixed|null $default default value to return
     *
     * @return mixed
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return C::Arrays()->get($this->config, $key, $default);
    }

    /**
     * Enable handling of preflight requests for CORS
     *
     * This will automatically handle the "OPTIONS" request and return an empty
     * body which is needed for most APIs with CORS.
     *
     * Explanation: https://developer.mozilla.org/en-US/docs/Glossary/Preflight_request
     */
    public function enablePreflightHandling(): void
    {
        if (C::Server()->get('REQUEST_METHOD') == "OPTIONS") {
            // Basic headers are provided by nginx. No Access-Control needed again...
            header("Content-Length: 0");
            header("Content-Type: text/plain");
            exit(0);
        }
    }

}