<?php
/**
 * This file contains the init class for array helper.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Arrays
 *
 * Arrays module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Arrays extends Module implements ModuleInterface
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
     * @param array  $arr input array
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
     * @param array       $arr     input array
     * @param string      $key     the key
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

    /**
     * Sort an array by two sub-array values
     *
     * The first one is more important (e.g. priority) than the second (e.g. date, count, ...)
     * The array $arr must consist of sub-arrays. Every sub-array must have at least the 2 keys
     *
     * This sort function is case-insensitive!
     *
     * Idea from: http://stackoverflow.com/a/16832208
     *
     * Example input array, $key1 = 'foo', $key2 = 'bar'
     * $arr = [
     *     ['foo' => 1, 'bar' => 2],
     *     ['foo' => 5, 'bar' => 3, 'baz' => 1]
     * ]
     *
     * @param array  $arr   the input array
     * @param string $key1  priority key
     * @param string $key2  second key
     * @param string $order (opt.) sort direction (desc / asc). Default: desc
     */
    public function sortByTwoKeys(&$arr, $key1, $key2, $order = 'desc')
    {
        uasort($arr, function ($a, $b) use ($key1, $key2, $order) {

            if (strtolower($order) == 'asc') {
                $a_tmp = $a;
                $a = $b;
                $b = $a_tmp;
            }

            if ($a[$key1] == $b[$key1]) {
                return strtolower($a[$key2]) < strtolower($b[$key2]) ? 1 : -1;
            }

            return strtolower($a[$key1]) < strtolower($b[$key1]) ? 1 : -1;
        });
    }

    /**
     * Check if an array has duplicates
     *
     * Idea: http://stackoverflow.com/a/3145647
     *
     * @param $array
     *
     * @return bool
     */
    public function hasDuplicates($array)
    {
        return count($array) !== count(array_unique($array));
    }

}