<?php
/**
 * This file contains the init class for config.
 */

namespace Charm\Vivid\Kernel\Modules;

use Carbon\Carbon;
use Charm\Vivid\Charm;
use Charm\Vivid\Exceptions\LogicException;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\PathFinder;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Config
 *
 * Config module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Config implements ModuleInterface
{
    /** @var string  the module delimiter */
    protected $module_delimiter = "#";

    /** @var string  the filename delimiter */
    protected $file_delimiter = ":";

    /**
     * Module init
     */
    public function loadModule()
    {
        // Load system config

        // Load date time config
        // Set time zone
        date_default_timezone_set($this->get('main:local.timezone'));
        // Set date time formatting and language
        setlocale(LC_ALL, $this->get('main:local.language'));
        // Set Carbon
        Carbon::setLocale($this->get('main:local.shortlang'));
    }

    /**
     * Get config value
     *
     * @param string     $key     the key
     * @param null|mixed $default (optional) default value to return. Default: null
     * @param bool       $cache   (optional) use cache for configuration? Default: true
     *
     * @return mixed
     */
    public function get($key, $default = null, $cache = true)
    {
        // Separate wanted array key from whole string

        // Module and filename
        $mod_parts = explode($this->module_delimiter, $key);

        // Default module: App
        $module = 'App';

        // Got custom module?
        if(count($mod_parts) == 2) {
            // Set module and remove from string
            $module = array_shift($mod_parts);
            $key = implode($this->module_delimiter, $mod_parts);
        }

        // Filename and config
        $wanted_key_parts = explode($this->file_delimiter, $key);
        $wanted_key = array_pop($wanted_key_parts);
        $filename = implode($this->file_delimiter, $wanted_key_parts);

        // Get from app storage if stored
        $cache_key = 'CF-' . $module . '-' . $filename;
        if($cache && Charm::AppStorage()->has('Config', $cache_key)) {
            return Charm::AppStorage()->aget('Config', $cache_key, $wanted_key, $default);
        }

        // Not found or no cache usage -> return data from file
        $file = $this->getConfigFile($key, false, $module);
        $content = file_get_contents($file);

        // Parse yaml content
        $yaml = Yaml::parse($content);

        // Get environment config file
        $envfile = $this->getConfigFile($key, true, $module);
        if(file_exists($envfile)) {
            // File existing. Get data
            $envcontent = file_get_contents($envfile);

            // Also parse YAML and merge with main config array
            // This will override values keeping the old ones
            $envyaml = Yaml::parse($envcontent);

            // Merge them
            $yaml = Charm::Arrays()->array_merge_recursive($yaml, $envyaml);
        }

        // Store whole config in cache
        // No expiration because config is persistent until it gets removed by command.
        Charm::AppStorage()->set('Config', $cache_key, $yaml);

        // Return found value
        return Charm::get('Arrays')->get($yaml, $wanted_key, $default);
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function inDebugMode()
    {
        return $this->get('main:debug.debugmode') == true;
    }

    /**
     * Set a config value
     *
     * @param string $key   the key
     * @param mixed  $value the value
     *
     * @return bool
     */
    public function set($key, $value)
    {
        // TODO Implement.
    }

    /**
     * Add a config value
     *
     * @param string $key   the key
     * @param mixed  $value the value
     *
     * @return bool
     */
    public function add($key, $value)
    {
        // TODO Implement.
    }

    /**
     * Delete a config entry
     *
     * @param string $key the key
     *
     * @return bool
     */
    public function delete($key)
    {
        // TODO Implement.
    }

    /**
     * Get the absolute path to the config file
     *
     * @param string  $key     config key
     * @param bool    $env     (opt.) get path to environment specific config file? Default: false
     * @param string  $module  (opt.) name of module where the config file is located
     *
     * @return string
     *
     * @throws LogicException
     */
    private function getConfigFile($key, $env = false, $module = 'App')
    {
        if(!Charm::has($module)) {
            throw new LogicException("Cannot load config. Module '" . $module . "' is not loaded. Requested key: " . $key);
        }

        // Default case: app config
        $path = PathFinder::getModulePath($module) . DIRECTORY_SEPARATOR . 'Config';

        // Get filename
        if(!in_string($this->file_delimiter, $key)) {
            throw new LogicException("No config file supplied for config: " . $key);
        }

        $parts = explode($this->file_delimiter, $key);
        $filename = array_shift($parts);

        // Local path?
        if($env) {
            $path .= DIRECTORY_SEPARATOR . 'Environments' . DIRECTORY_SEPARATOR . Charm::App()->getEnvironment();
        }

        return $path . DIRECTORY_SEPARATOR . $filename . '.yaml';
    }

}