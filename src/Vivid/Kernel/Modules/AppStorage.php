<?php
/**
 * This file contains the init class for app storage.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Handler;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\Router\Router;

/**
 * Class AppStorage
 *
 * AppStorage module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class AppStorage extends Module implements ModuleInterface
{
    /** @var array the storage array */
    protected $storage = [];

    protected $cache_file;

    /**
     * Module init
     */
    public function loadModule()
    {
        // Basic storage structure
        $this->storage = [
            'View' => [
                'Head' => [],
                'Body' => [],
            ],
            'Routes' => [],
        ];

        // Set path to cache file
        $this->cache_file = cPath('/var/cache') . DS . 'charm_appstorage.cache';

        // Load stored init array from cache for better performance
        $this->loadInitStorage();
    }

    /**
     * Get whole storage
     *
     * This should only be used for debugging or testing!
     *
     * @return array
     */
    public function getAll()
    {
        return $this->storage;
    }

    /**
     * Get something from the app storage
     *
     * @param string      $module  name of module
     * @param string      $key     the key or "*" for all keys in the module
     * @param string|null $default (opt.) the default value (null)
     *
     * @return mixed  the value or $default
     */
    public function get($module, $key, $default = null)
    {
        if (!array_key_exists($module, $this->storage)
            || !array_key_exists($key, $this->storage[$module])
        ) {
            return $default;
        }

        if ($key == "*") {
            return $this->storage[$module];
        }

        return C::Arrays()->get($this->storage[$module], $key, $default);
    }

    /**
     * Check if a key exists
     *
     * @param string $module name of module
     * @param string $key    the key
     *
     * @return bool
     */
    public function has($module, $key)
    {
        return array_key_exists($module, $this->storage) && C::Arrays()->has($this->storage[$module], $key);
    }

    /**
     * Set a value in the app storage
     *
     * This will create or replace the stored element
     *
     * @param string $module name of module
     * @param string $key    the key or "*" to replace whole data of module
     * @param mixed  $value  the value to store
     *
     * @return bool
     */
    public function set($module, $key, $value)
    {
        if (!array_key_exists($module, $this->storage)) {
            $this->storage[$module] = [];
        }

        if ($key == "*") {
            return $this->storage[$module] = $value;
        }

        return $this->storage[$module][$key] = $value;
    }

    /**
     * Append a value
     *
     * @param string $module name of module
     * @param string $key    the key
     * @param mixed  $value  the value to append
     *
     * @return bool
     */
    public function append($module, $key, $value)
    {
        if (!array_key_exists($module, $this->storage)) {
            $this->storage[$module] = [];
        }

        if (!array_key_exists($key, $this->storage[$module])) {
            $this->storage[$module][$key] = [];
        }

        return $this->storage[$module][$key][] = $value;

    }

    /**
     * Delete a value from the app storage
     *
     * @param $module
     * @param $key
     *
     * @return bool
     */
    public function delete($module, $key)
    {
        if (!array_key_exists($module, $this->storage)
            || !array_key_exists($key, $this->storage[$module])
        ) {
            return false;
        }

        unset($this->storage[$module][$key]);
        return true;
    }

    /**
     * Get something from an array in the app storage
     *
     * @param string      $module  name of module
     * @param string      $arrname name of array
     * @param string      $key     the key
     * @param string|null $default (opt.) the default value (null)
     *
     * @return mixed|bool  false on error
     */
    public function aget($module, $arrname, $key, $default = null)
    {
        if (!array_key_exists($module, $this->storage) || !array_key_exists($arrname, $this->storage[$module])
        ) {
            return false;
        }

        return C::Arrays()->get($this->storage[$module][$arrname], $key, $default);
    }

    /**
     * Set a value in an array in the app storage
     *
     * This will create or replace the stored element
     *
     * @param string $module  name of module
     * @param string $arrname name of array
     * @param string $key     the key
     * @param mixed  $value   the value to store
     *
     * @return bool
     */
    public function aset($module, $arrname, $key, $value)
    {
        if (!array_key_exists($module, $this->storage)) {
            $this->storage[$module] = [];
        }

        if (!array_key_exists($arrname, $this->storage[$module])) {
            $this->storage[$module][$arrname] = [];
        }

        return $this->storage[$module][$arrname][$key] = $value;
    }

    /**
     * Clear the cache
     */
    public function clearCache()
    {
        if (file_exists($this->cache_file)) {
            unlink($this->cache_file);
        }
    }

    /**
     * Generate the cache and replace it
     */
    public function generateCache()
    {
        // Get all config files, so they are stored in the appstorage as well
        $this->addAllConfigFiles();

        // Same for routes data
        if (!C::has('Router')) {
            $router = new Router();
            $router->init();
        } else {
            C::Router()->init();
        }

        // Generate cache, override file
        file_put_contents($this->cache_file, serialize($this->storage));
    }

    /**
     * Load the cache into app storage
     */
    public function loadInitStorage()
    {
        if (file_exists($this->cache_file)) {
            $this->storage = unserialize(file_get_contents($this->cache_file));
        }
    }

    /**
     * Add all config files to the appstorage
     */
    private function addAllConfigFiles()
    {
        // Go through all modules
        $handler = Handler::getInstance();
        foreach ($handler->getModuleClasses() as $name => $module) {
            try {
                $mod = $handler->getModule($name);
                if (is_object($mod) && method_exists($mod, 'getReflectionClass')) {
                    $dir = $mod->getBaseDirectory() . DS . 'Config';

                    if (file_exists($dir)) {
                        // Load all files inside the Config dir except directories
                        $files = array_diff(scandir($dir), ['..', '.']);

                        foreach ($files as $file) {
                            if (str_contains($file, '.yaml')) {
                                $conf_name = str_replace('.yaml', '', $file);

                                // Get something from config file so it gets stored in the appstorage
                                C::Config()->get($name . '#' . $conf_name . ':foobar');
                            }
                        }

                    }
                }
            } catch (\Exception $e) {
                // Just continue...
            }
        }
    }

}