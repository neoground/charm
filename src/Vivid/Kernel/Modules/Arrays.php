<?php
/**
 * This file contains the init class for array helper.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Arrays
 *
 * Arrays module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Arrays implements ModuleInterface
{
    /**
     * Module init
     */
    public function loadModule()
    {
        $this->registerFunctions();
    }

    /**
     * Register array functions
     */
    private function registerFunctions()
    {
        // TODO: Add php functions for arrays (with !function_exist etc.)
    }

    /**
     * Check if array has value by key
     *
     * @param array $arr input array
     * @param string $key the key
     *
     * @return bool
     */
    public function has($arr, $key)
    {
        return !empty(self::get($arr, $key, false));
    }

    /**
     * Get specific value from array
     *
     * @param array $arr input array
     * @param string $key the key
     * @param null|string $default (optional) default value
     *
     * @return mixed
     */
    public function get($arr, $key, $default = null)
    {
        // Get key parts for multiple dimensions
        $keyparts = explode(".", $key);

        // a.b.c -> array([a][b][c])
        foreach ($keyparts as $keypart) {
            if (isset($arr[$keypart])) {
                $arr = $arr[$keypart];
            } else {
                $arr = $default;
            }
        }

        return $arr;
    }

    /**
     * Get the last element of an array without removing it
     *
     * Works like array_pop without removing the last element.
     * Idea from: http://stackoverflow.com/a/35957563/6026136
     *
     * @param $arr
     *
     * @return mixed
     */
    public function getLastElement($arr)
    {
        $x = array_slice($arr, -1);
        return array_pop($x);
    }

    /**
     * Merge two arrays recursively but without duplicated entries
     *
     * @see https://stackoverflow.com/a/25712428/6026136
     *
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    public function array_merge_recursive(&$array1, &$array2)
    {
        $array1 = (array)$array1;
        $array2 = (array)$array2;

        $merged = $array1;

        foreach ($array2 as $key => & $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->array_merge_recursive($merged[$key], $value);
            } else if (is_numeric($key)) {
                if (!in_array($value, $merged)) {
                    $merged[] = $value;
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

}