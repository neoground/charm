<?php
/**
 * This file contains the Header module.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Respect\Validation\Validator as RespectValidator;

/**
 * Class Header
 *
 * Header module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Header extends Module implements ModuleInterface
{
    /** @var array All request headers */
    protected array $request_headers;

    /** @var array Response headers */
    protected array $response_headers;

    /**
     * Load the module
     */
    public function loadModule()
    {
        // Load request headers
        if (function_exists('apache_request_headers')) {
            $this->request_headers = array_change_key_case(apache_request_headers());
        } else {
            // Fetch request headers in non-apache environments
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
            $this->request_headers = $arh;
        }
    }

    /**
     * Get a specific request header
     *
     * @param string     $key     header key
     * @param mixed|null $default (optional) default parameter
     *
     * @return null|string
     */
    public function getHeader(string $key, mixed $default = null): mixed
    {
        return C::Arrays()->get($this->request_headers, strtolower($key), $default);
    }


}