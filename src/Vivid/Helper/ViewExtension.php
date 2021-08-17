<?php
/**
 * This file contains the global ViewExtension class
 */

namespace Charm\Vivid\Helper;

use Carbon\Carbon;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Output\View;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class ViewExtension
 *
 * Adding basic view functions to twig views and much more!
 *
 * @package Charm\Vivid\Helper
 */
class ViewExtension extends AbstractExtension
{
    /**
     * Set array of all functions to add to twig
     *
     * @return array|TwigFunction[]
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
                $arr[$method] = new TwigFunction($method, [$this, $method]);
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
        return C::Router()->getAssetsUrl();
    }

    /**
     * Get the base URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return C::Router()->getBaseUrl();
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
        return C::Router()->buildUrl($name, $args);
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
        return C::Router()->getCurrentUrl();
    }

    /**
     * Get base url relative to base directory
     *
     * @return string
     */
    public function getRelativeUrl()
    {
        return C::Router()->getRelativeUrl();
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
        return C::Config()->get($key, $default);
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
        return C::Formatter()->formatDate($data);
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
        return C::Formatter()->formatDateShort($data);
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
        return C::Formatter()->formatDateTimeShort($data);
    }

    /**
     * Format money / currencies
     *
     * @param string  $data      input value
     * @param int     $decimals  (opt.) the decimals (default: 2)
     * @param string  $decimal   (opt.) decimal separator
     * @param string  $thousands (opt.) thousands separator
     *
     * @return int|string
     */
    public function formatMoney($data, $decimals = 2, $decimal = null, $thousands = null)
    {
        return C::Formatter()->formatMoney($data, $decimals, $decimal, $thousands);
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
        return C::Config()->get('main:debug.debugmode', false);
    }

    /**
     * Get the name of the current environment
     *
     * @return string
     */
    public function getEnvironment()
    {
        return C::App()->getEnvironment();
    }

    /**
     * In string method
     *
     * @deprecated Legacy support. Use str_contains instead
     *
     * @param string $needle   what we look for
     * @param string $haystack what we have
     *
     * @return bool
     */
    public function in_string($needle, $haystack)
    {
        return str_contains($haystack, $needle);
    }

    /**
     * String contains method
     *
     * @param string $haystack what we have
     * @param string $needle   what we look for
     *
     * @return bool
     */
    public function str_contains($haystack, $needle)
    {
        return str_contains($haystack, $needle);
    }

    /**
     * Get template path for usage in twig iself
     *
     * Contains support for modules
     *
     * @param string $name charm's template name (e.g. Module#foo.bar)
     *
     * @return string
     */
    public function getTemplateByName(string $name)
    {
        return View::getTemplateByName($name);
    }

    /**
     * Access the magic magnet
     *
     * @param string $module name of module, normally used as C::name()
     *
     * @return mixed
     */
    public function c(string $module)
    {
        return C::get($module);
    }

    /**
     * Get the PHP version
     *
     * @return string
     */
    public function getPhpVersion() : string
    {
        $parts = explode("+", phpversion());
        return $parts[0];
    }

    /**
     * Get max size of an upload
     *
     * Thanks to: https://www.kavoir.com/2010/02/php-get-the-file-uploading-limit-max-file-size-allowed-to-upload.html
     *
     * @param bool $only_number only return number? Default: false
     *
     * @return string
     */
    public function getMaxUploadSize(bool $only_number = false) : string
    {
        $max_upload = (int)(ini_get('upload_max_filesize'));
        $max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);

        if($only_number) {
            return $upload_mb;
        }

        return $upload_mb . ' MB';
    }

}