<?php
/**
 * This file contains the PathFinder class
 */

namespace Charm\Vivid;
use Charm\Vivid\Kernel\Handler;
use Charm\Vivid\Kernel\Modules\Config;

/**
 * Class PathFinder
 *
 * path and url class
 *
 * @deprecated 2020-08-30 please use C::Storage() instead. This class will be removed soon.
 *
 * @package Charm\Vivid
 */
class PathFinder
{
    protected $path;

    /**
     * Get the absolute path to the log directory
     *
     * @return string
     */
    public static function getLogPath()
    {
        return cPath('/var/logs');
    }

    /**
     * Get the absolute path to the var directory
     *
     * @return string
     */
    public static function getVarPath()
    {
        return cPath('/var');
    }

    /**
     * Get the absolute path to the cache directors
     * 
     * @return string
     */
    public static function getCachePath()
    {
        return cPath('/var/cache');
    }

    /**
     * Get the absolute path to the app directory
     *
     * @return string
     */
    public static function getAppPath()
    {
        return self::getModulePath('App');
    }

    /**
     * Get the absolute path to the module base directory
     *
     * @param string  $module  name of module
     *
     * @return string
     */
    public static function getModulePath($module)
    {
        return Charm::get($module)->getBaseDirectory();
    }

    /**
     * Get the absolute path to the assets directory
     *
     * @return string
     */
    public static function getAssetsPath()
    {
        return cPath('/assets');
    }

    /**
     * Get the absolute path to the data directory
     *
     * @return string
     */
    public static function getDataPath()
    {
        return cPath('/data');
    }

    /**
     * Format absolute path to file to URL to file
     *
     * @param string $path the absolute path
     *
     * @return string
     */
    public static function pathToUrl($path)
    {
        return str_replace(cPath('/'), Charm::Router()->getBaseUrl(), $path);
    }

    /**
     * Create a new PathFinder object
     *
     * @param string $path absolute path to directory or file
     *
     * @return PathFinder
     */
    public static function setPath($path)
    {
        $x = new self;
        $x->path = $path;
        return $x;
    }

    /**
     * Get URL to provided path / file
     *
     * @return string
     */
    public function getUrl()
    {
        return self::pathToUrl($this->path);
    }

    /**
     * Create all directories in this path if they not exist
     *
     * @param int $mode chmod value (default: 0777)
     *
     * @return bool  true if created or already existing, false on failure
     */
    public function createDirectoriesIfNotExisting($mode = 0777)
    {
        $path = $this->path;
        if(!is_dir($path)) {
            // Got path to file.
            $path = dirname($path);
        }

        if (!file_exists($path)) {
            return mkdir($path, $mode, true);
        }

        // Already existing
        return true;
    }

    /**
     * Get the path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

}