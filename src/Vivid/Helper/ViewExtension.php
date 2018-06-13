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
    public function getAssetUrl()
    {
        return Charm::Router()->getBaseUrl() . '/assets';
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
     * Get the current full url
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        return Charm::Router()->getCurrentUrl();
    }

    /**
     * Get the currently logged in user
     */
    public function getCurrentUser()
    {
        return Charm::Guard()->getUser();
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

}