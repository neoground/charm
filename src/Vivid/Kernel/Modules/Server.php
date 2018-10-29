<?php
/**
 * This file contains the Server module.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Server
 *
 * Server module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Server implements ModuleInterface
{
    /**
     * Load the module
     */
    public function loadModule()
    {
        // Nothing to do here yet.
    }

    /**
     * Get a $_SERVER value
     *
     * @param $key     string     the wanted key, arrays separated by .
     * @param $default null|mixed (optional) default parameter
     *
     * @return null|string|array
     */
    public function get($key, $default = null)
    {
        return Charm::Arrays()->get($_SERVER, $key, $default);
    }

    /**
     * Check if $_SERVER contains this key
     *
     * @param string $key the key
     *
     * @return bool
     */
    public function has($key)
    {
        return Charm::Arrays()->has($_SERVER, $key);
    }

    /**
     * Get the full $_SERVER array
     *
     * @return array
     */
    public function getAll()
    {
        return $_SERVER;
    }

}