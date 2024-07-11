<?php
/**
 * This file contains charm's own functions and globals
 */

use Charm\Vivid\C;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!function_exists('cPath')) {

    /**
     * Get the path to a project directory
     *
     * Without leading slash
     *
     * @param string $subdir (optional) subdirectory
     *
     * @return string
     */
    function cPath(string $subdir = '/'): string
    {
        $path = null;

        // Use script filename path by default
        if (array_key_exists('SCRIPT_FILENAME', $_SERVER)) {
            $path = dirname($_SERVER['SCRIPT_FILENAME']);
        }

        // If you use command line the path can be relative. We need an absolute path!
        if (defined('CLI_PATH')) {
            // Get absolute path provided by argv
            $path = dirname(realpath(CLI_PATH));
        }

        // Append subdirectory
        $path .= '/' . ltrim($subdir, '/');

        return rtrim($path, '/');
    }
}

if (!function_exists('cBaseUrl')) {

    /**
     * Get the base URL for URL generation.
     *
     * Without trailing slash
     *
     * @return string
     */
    function cBaseUrl(): string
    {
        $pathInfo = pathinfo($_SERVER['PHP_SELF']);
        $protocol = C::Request()->isHttpsRequest() ? 'https://' : 'http://';

        if (!array_key_exists('HTTP_HOST', $_SERVER)) {
            // Rare case of unset HTTP_HOST, use fallback
            return C::Config()->get('main:request.base_url', '');
        }

        return rtrim($protocol . $_SERVER['HTTP_HOST'] . $pathInfo['dirname'], '/');
    }

}

if (!function_exists('cCurrentUrl')) {

    /**
     * Get the current URL
     *
     * @return string
     */
    function cCurrentUrl(): string
    {
        $protocol = C::Request()->isHttpsRequest() ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

}
