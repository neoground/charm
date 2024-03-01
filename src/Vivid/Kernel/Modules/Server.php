<?php
/**
 * This file contains the Server module.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Server
 *
 * Server module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Server extends Module implements ModuleInterface
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
        return C::Arrays()->get($_SERVER, $key, $default);
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
        return C::Arrays()->has($_SERVER, $key);
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

    /**
     * Retrieves the operating system of the current server.
     *
     * @return string The name of the operating system.
     */
    public function getOperatingSystem(): string
    {
        return php_uname('s');
    }

    /**
     * Retrieves the hostname of the current server.
     *
     * @return string The name of the operating system.
     */
    public function getHostname(): string
    {
        return php_uname('n');
    }

    /**
     * Retrieves the operating system release name + version of the current server.
     *
     * @return string The name of the operating system.
     */
    public function getVersion(): string
    {
        return php_uname('r') . ' ' . php_uname('v');
    }

    /**
     * Retrieves the machine type of the current server (e.g. i386).
     *
     * @return string The name of the operating system.
     */
    public function getMachineType(): string
    {
        return php_uname('m');
    }

}