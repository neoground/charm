<?php
/**
 * This file contains the global ViewExtension class
 */

namespace Charm\Vivid\Helper;

use Carbon\Carbon;
use Charm\Vivid\Base\BasicViewExtension;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Output\View;

/**
 * Class ViewExtension
 *
 * Adding basic view functions to twig views and much more!
 *
 * @package Charm\Vivid\Helper
 */
class ViewExtension extends BasicViewExtension
{
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
     * @param array|string|null   $args  (optional) array with values for all variables in route
     *
     * @return string
     */
    public function getUrl(string $name, array|string|null $args = [])
    {
        return C::Router()->getUrl($name, $args);
    }

    /**
     * Construct a URL
     *
     * @param string $url the input URL, can include query parameters and more
     * @param array  $params an array with query parameters to add (key => value), will override existing ones
     *                       and append new ones
     *
     * @return string the new URL
     */
    public function constructUrl(string $url, array $params): string
    {
        return C::Router()->constructUrl($url, $params);
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
     * Format a date as human diff (relative, e.g. 3 months ago)
     *
     * Will return the date (see formatDate()) if longer ago than $date_after_days days
     *
     * @param $date
     * @param int $date_after_days return date instead of diff if older than this. Set 0 to disable
     *
     * @return string
     */
    public function formatDateDiff($date, $date_after_days = 365)
    {
        return C::Formatter()->formatDateDiff($date, $date_after_days);
    }

    /**
     * Format a number for displaying
     *
     * @param string  $data      input value
     * @param int     $decimals  (opt.) the decimals (default: 2)
     * @param string  $decimal   (opt.) decimal separator
     * @param string  $thousands (opt.) thousands separator
     *
     * @return int|string
     */
    public function formatNumber($data, $decimals = 2, $decimal = null, $thousands = null)
    {
        return C::Formatter()->formatNumber($data, $decimals, $decimal, $thousands);
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
        return (empty($input)) ? '' : str_replace($search, $replace, $input);
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
     * String contains method
     *
     * @param string $haystack what we have
     * @param string $needle   what we look for
     *
     * @return bool
     */
    public function str_contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * Truncate a string
     *
     * @param string $str    input string
     * @param int    $length wanted length
     * @param string $append optional string to append
     *
     * @return string
     */
    public function truncate(string $str, int $length, string $append = '…')
    {
        if(mb_strlen($str) <= $length) {
            return $str;
        }

        return mb_substr($str, 0, $length) . $append;
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
    public function getTemplateByName(string $name): string
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
     * Get the charm version
     *
     * @return string
     */
    public function getCharmVersion() : string
    {
        return C::VERSION;
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

    /**
     * Translate a text string
     *
     * The text can include variables, like {name}. Key needs to be lowercase.
     * This will be replaced by the value of $vars['name'].
     *
     * If text was not found, $default will be used. Variables will be applied as well.
     *
     * @param string  $key
     * @param array   $vars
     * @param string  $default
     *
     * @return mixed
     */
    public function __(string $key, array $vars = [], mixed $default = ''): mixed
    {
        return C::Formatter()->translate($key, $vars, $default);
    }

    /**
     * Get language string
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return C::Formatter()->getLanguage();
    }

    /**
     * Get base path to a module
     *
     * @param string $module the module's name
     *
     * @return string
     */
    public function getModuleUrl(string $module) : string
    {
        return C::Storage()->pathToUrl(C::Storage()->getModulePath($module));
    }

}