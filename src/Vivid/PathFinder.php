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
 * @package Charm\Vivid
 */
class PathFinder
{
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

}