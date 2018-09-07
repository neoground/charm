<?php
/**
 * This file contains the global ViewExtension class
 */

namespace Charm\Vivid\Helper;

use Carbon\Carbon;
use Charm\Vivid\Charm;

/**
 * Class ViewExtension
 *
 * Adding basic view functions to twig views and much more!
 *
 * @package Charm\Vivid\Helper
 */
class ViewExtension extends \Twig_Extension
{
    /**
     * Set array of all functions to add to twig
     *
     * @return array|\Twig_Function[]
     */
    public function getFunctions()
    {
        // Get all functions in this class
        $methods = get_class_methods($this);

        // Methods to ignore (from parent)
        $ignore = [
            'getTokenParsers',
            'getNodeVisitors',
            'getFilters',
            'getTests',
            'getFunctions',
            'getOperators'
        ];

        $arr = [];

        // Build array, remove twig methods
        foreach($methods as $method) {
            if(!in_array($method, $ignore)) {
                $arr[$method] = new \Twig_Function($method, [$this, $method]);
            }
        }

        return $arr;
    }

    /**
     * Get the asset URL
     *
     * @return string
     */
    public function getAssetsUrl()
    {
        return Charm::Router()->getAssetsUrl();
    }

    /**
     * Get the base URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return Charm::Router()->getBaseUrl();
    }

    /**
     * Build URL based on route
     *
     * @param string         $name  name of route
     * @param array|string   $args  (optional) array with values for all variables in route
     *
     * @return string
     */
    public function getUrl($name, $args = [])
    {
        return Charm::Router()->buildUrl($name, $args);
    }

    /**
     * Build URL based on route
     *
     * Providing the same method name for easier usage.
     *
     * @param string         $name  name of route
     * @param array|string   $args  (optional) array with values for all variables in route
     *
     * @return string
     */
    public function buildUrl($name, $args = [])
    {
        return $this->getUrl($name, $args);
    }

    /**
     * Get the current full url
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        return Charm::Router()->getCurrentUrl();
    }

    /**
     * Get relative URL
     *
     * @return string
     */
    public function getRelativeUrl()
    {
        return Charm::Router()->getRelativeUrl();
    }

    /**
     * Get config value
     *
     * @param string     $key     the key
     * @param null|mixed $default (optional) default value to return. Default: null
     *
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        return Charm::Config()->get($key, $default);
    }

    /**
     * Add option to select
     *
     * @param string      $val     option value
     * @param string|null $display (opt.) display text (empty: $val used)
     * @param mixed       $sel     selected value for comparision
     *
     * @return string
     */
    public function formOption($val, $display = null, $sel = null)
    {
        // Selected?
        $select = '';
        if($val == $sel) {
            $select = 'selected';
        }

        // Use value as display fallback
        if(empty($display)) {
            $display = $val;
        }

        return '<option value="' . $val . '" ' . $select . '>' . $display . '</option>';
    }

    /**
     * Format a date localized in a format specified in main.yaml
     *
     * @param string|Carbon $data the date
     *
     * @return bool|string
     */
    public function formatDate($data)
    {
        return Charm::Formatter()->formatDate($data);
    }

    /**
     * Format a date localized in a short format specified in main.yaml
     *
     * @param string|Carbon  $data  the date
     *
     * @return bool|string
     */
    public function formatDateShort($data)
    {
        return Charm::Formatter()->formatDateShort($data);
    }

    /**
     * Format a date with time localized in a short format specified in main.yaml
     *
     * @param string|Carbon  $data  the date
     *
     * @return bool|string
     */
    public function formatDateTimeShort($data)
    {
        return Charm::Formatter()->formatDateTimeShort($data);
    }

    /**
     * Format money / currencies
     *
     * @param string  $data      input value
     * @param int     $decimals  (opt.) the decimals (default: 2)
     *
     * @return int|string
     */
    public function formatMoney($data, $decimals = 2)
    {
        return Charm::Formatter()->formatMoney($data, $decimals);
    }

    /**
     * Get carbon date instance
     *
     * @param mixed $data
     *
     * @return Carbon
     */
    public function date($data)
    {
        return Carbon::parse($data);
    }

    /**
     * String replacement for twig views
     *
     * @param mixed $search
     * @param mixed $replace
     * @param string $input  the input string
     *
     * @return string
     */
    public function str_replace($search, $replace, $input)
    {
        return str_replace($search, $replace, $input);
    }

    /**
     * Is debug mode enabled?
     *
     * @return mixed
     */
    public function isDebug()
    {
        return Charm::Config()->get('main:debug.debugmode', false);
    }

    /**
     * In string method
     *
     * @param string $needle   what we look for
     * @param string $haystack what we have
     *
     * @return bool
     */
    public function in_string($needle, $haystack)
    {
        return in_string($needle, $haystack);
    }

}