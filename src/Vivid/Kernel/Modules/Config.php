<?php
/**
 * This file contains the init class for config.
 */

namespace Charm\Vivid\Kernel\Modules;

use Carbon\Carbon;
use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Exceptions\LogicException;
use Charm\Vivid\Exceptions\ModuleNotFoundException;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\Kernel\Output\Json;
use Charm\Vivid\Kernel\Output\View;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Config
 *
 * Config module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Config extends Module implements ModuleInterface
{
    /** @var string  the module delimiter */
    protected string $module_delimiter = "#";

    /** @var string  the filename delimiter */
    protected string $file_delimiter = ":";

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
     * After init is complete check for maintenance mode and abort if app is down
     */
    public function postInit()
    {
        if($this->inMaintenanceMode()) {
            $this->handleMaintenanceMode();
        }
    }

    /**
     * Handles the maintenance mode for the application.
     *
     * This method is responsible for handling the maintenance mode of the application.
     * It checks the configuration settings to determine the output format (JSON or HTML).
     * If the output format is JSON, it outputs JSON response with appropriate error message.
     * Otherwise, it outputs HTML view for the maintenance mode.
     *
     * After that the app is terminated!
     */
    private function handleMaintenanceMode(): void
    {
        // Output JSON for API
        $error_style = $this->getModule('Config')->get('main:output.maintenance_style', 'default');
        if ($this->getModule('Request')->accepts('json') || $error_style == 'json') {
            $output = Json::makeErrorMessage('AppDown', 500);
            echo $output->render();

            return;
        }

        // Output HTML in every other case
        $tpl = $this->getModule('Config')->get('main:output.maintenance_view', 'maintenance');
        $view = View::make($tpl, 500);

        echo $view->render();

        C::shutdown();
    }

    /**
     * Get config value
     *
     * @param string     $key     the key
     * @param mixed|null $default (optional) default value to return. Default: null
     * @param bool       $cache   (optional) use cache for configuration? Default: true
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null, bool $cache = true): mixed
    {
        // Separate wanted array key from whole string

        // Module and filename
        $mod_parts = explode($this->module_delimiter, $key);

        // Default module: App
        $module = 'App';

        // Got custom module?
        if (count($mod_parts) == 2) {
            // Set module and remove from string
            $module = array_shift($mod_parts);
            $key = implode($this->module_delimiter, $mod_parts);
        }

        // Check for custom config override via C::Config()->set(...)
        $custom_cache_key = 'CustomConfig-' . $module . '-' . $key;
        if (C::AppStorage()->has('Config', $custom_cache_key)) {
            return C::AppStorage()->get('Config', $custom_cache_key);
        }

        // App module can override config
        // So return config from App module if existing
        if ($module != 'App') {
            $ret = $this->get($key);
            if ($ret !== $default) {
                return $ret;
            }
        }

        // Filename and config
        $wanted_key_parts = explode($this->file_delimiter, $key);
        $wanted_key = array_pop($wanted_key_parts);
        $filename = implode($this->file_delimiter, $wanted_key_parts);

        // Get from app storage if stored
        $cache_key = 'CF-' . $module . '-' . $filename;
        if ($cache && C::AppStorage()->has('Config', $cache_key)) {
            return C::AppStorage()->aget('Config', $cache_key, $wanted_key, $default);
        }

        // Not found or no cache usage -> return data from file
        $file = $this->getConfigFile($key, false, $module);

        // Return default if file is not existing
        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);

        // Parse yaml content
        $yaml = Yaml::parse($content);

        // Get environment config file
        $envfile = $this->getConfigFile($key, true, $module);
        if (file_exists($envfile)) {
            // File existing. Get data
            $envcontent = file_get_contents($envfile);

            // Also parse YAML and merge with main config array
            // This will override values keeping the old ones
            $envyaml = Yaml::parse($envcontent);

            // Merge them
            $yaml = C::Arrays()->array_merge_recursive($yaml, $envyaml);
        }

        // Store whole config in cache
        // No expiration because config is persistent until it gets removed by command.
        C::AppStorage()->set('Config', $cache_key, $yaml);

        // Return found value
        return C::get('Arrays')->get($yaml, $wanted_key, $default);
    }

    /**
     * Get formatted config value
     *
     * Gets a value from config and applies
     * vsprintf with $values (good for i18n)
     *
     * @param string     $key     the key
     * @param array      $values  (optional) values to insert by vsprintf
     * @param mixed|null $default (optional) default value to return. Default: null
     * @param bool       $cache   (optional) use cache for configuration? Default: true
     *
     * @return mixed
     */
    public function getf(string $key, array $values = [], mixed $default = null, bool $cache = true): mixed
    {
        $get = $this->get($key, null, $cache);

        if ($get === null) {
            return $default;
        }

        return vsprintf($get, $values);
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function inDebugMode(): bool
    {
        return $this->get('main:debug.debugmode') == true;
    }

    /**
     * Checks whether the application is in maintenance mode.
     *
     * This method checks if the 'maintenance.lock' file exists in the system's variable path.
     * If the file exists, it indicates that the application is in maintenance mode.
     * Otherwise, it implies that the application is not in maintenance mode.
     *
     * @return bool Returns true if the application is in maintenance mode, false otherwise.
     */
    public function inMaintenanceMode(): bool
    {
        return file_exists(C::Storage()->getVarPath() . DS . 'maintenance.lock');
    }

    /**
     * Turns on the maintenance mode for the application.
     *
     * This method creates an empty 'maintenance.lock' file in the system's variable path
     * to indicate that the application is in maintenance mode.
     *
     * @return bool Returns true if the maintenance mode is successfully turned on, false otherwise.
     */
    public function turnMaintenanceModeOn(): bool
    {
        return (bool) file_put_contents(C::Storage()->getVarPath() . DS . 'maintenance.lock', '');
    }

    /**
     * Turns off the maintenance mode for the application.
     *
     * This method deletes the 'maintenance.lock' file in the system's variable path,
     * which indicates that the application is no longer in maintenance mode.
     *
     * @return bool Returns true if the maintenance mode is successfully turned off, false otherwise.
     */
    public function turnMaintenanceModeOff(): bool
    {
        return C::Storage()->deleteFileIfExists(C::Storage()->getVarPath() . DS . 'maintenance.lock');
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
        $mod_parts = explode($this->module_delimiter, $key);

        // Default module: App
        $module = 'App';

        // Got custom module?
        if (count($mod_parts) == 2) {
            // Set module and remove from string
            $module = array_shift($mod_parts);
            $key = implode($this->module_delimiter, $mod_parts);
        }

        $cache_key = 'CustomConfig-' . $module . '-' . $key;
        return C::AppStorage()->set('Config', $cache_key, $value);
    }

    /**
     * Get the absolute path to the config file
     *
     * @param string $key    config key
     * @param bool   $env    (opt.) get path to environment specific config file? Default: false
     * @param string $module (opt.) name of module where the config file is located
     *
     * @return string
     *
     * @throws LogicException|ModuleNotFoundException
     */
    private function getConfigFile(string $key, bool $env = false, string $module = 'App'): string
    {
        // Default case: app config
        $path = C::get($module)->getBaseDirectory() . DS . 'Config';

        // Get filename
        if (!str_contains($key, $this->file_delimiter)) {
            throw new LogicException("No config file supplied for config: " . $key);
        }

        $parts = explode($this->file_delimiter, $key);
        $filename = array_shift($parts);

        // Local path?
        if ($env) {
            $path .= DS . 'Environments' . DS . C::App()->getEnvironment();
        }

        return $path . DS . $filename . '.yaml';
    }

}