<?php
/**
 * This file contains charm's own functions
 */

if(!function_exists('cPath')) {

    /**
     * Get the path to a project directory
     *
     * Without leading slash
     *
     * @param string $subdir (optional) sub directory
     *
     * @return string
     */
    function cPath($subdir = '/')
    {
        $path = null;

        // Use script filename path by default
        if(array_key_exists('SCRIPT_FILENAME', $_SERVER)) {
            $path = dirname($_SERVER['SCRIPT_FILENAME']);
        }

        // If you use command line the path can be relative. We need an absolute path!
        if(defined('CLI_PATH')) {
            // Get absolute path provided by argv
            $path = dirname(realpath(CLI_PATH));
        }

        // Append sub directory
        $path .= '/' . ltrim($subdir, '/');

        return rtrim($path, '/');
    }
}

if(!function_exists('cBaseUrl')) {

    /**
     * Get the base URL for URL generation.
     *
     * Without leading slash
     *
     * @return string
     */
    function cBaseUrl()
    {
        $pathInfo = pathinfo($_SERVER['PHP_SELF']);
        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

        // TODO: Irgendwie dynamisieren
        if (!array_key_exists('HTTP_HOST', $_SERVER)) {
            return 'https://example.com';
        }

        return rtrim($protocol . $_SERVER['HTTP_HOST'] . $pathInfo['dirname'], '/');
    }

}

if(!function_exists('cCurrentUrl')) {

    /**
     * Get the current URL
     *
     * @return string
     */
    function cCurrentUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

}

/**
 * Manually set apache_request_headers. Needed if php doesn't run as apache module
 * Source: https://secure.php.net/manual/en/function.apache-request-headers.php
 */
if (!function_exists('apache_request_headers')) {

    /**
     * Fetch all HTTP request headers
     *
     * @return array|false
     */
    function apache_request_headers()
    {
        $arh = [];
        $rx_http = '/\AHTTP_/';
        foreach ($_SERVER as $key => $val) {
            if (preg_match($rx_http, $key)) {
                $arh_key = preg_replace($rx_http, '', $key);
                // do some nasty string manipulations to restore the original letter case
                // this should work in most cases
                $rx_matches = explode('_', $arh_key);
                if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                    foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return ($arh);
    }

}