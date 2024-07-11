<?php
/**
 * This file contains the Header module.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

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
            $this->request_headers = array_change_key_case($arh);
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
    public function get(string $key, mixed $default = null): mixed
    {
        return C::Arrays()->get($this->request_headers, strtolower($key), $default);
    }

    /**
     * Set a response header
     *
     * Please note that this might be overwritten by headers set in the response
     * output itself and might lead to problems if you set them too, like
     * Content-Type, Content-Length or Last-Modified.
     *
     * @param string $key   response header key
     * @param mixed  $value response header value
     *
     * @return $this
     */
    public function setResponseHeader(string $key, mixed $value): static
    {
        $this->response_headers[$key] = $value;
        return $this;
    }

    /**
     * Send (output) all response headers
     *
     * @return void
     */
    public function sendResponseHeaders(): void
    {
        foreach ($this->response_headers as $k => $v) {
            header($k . ': ' . $v);
        }
    }

    /**
     * Set the status code of the response
     *
     * @param int $response_code The optional response_code will set the response code.
     *
     * @return int|bool The current response code. By default, the return value is int(200).
     */
    public function setResponseStatusCode(int $response_code): bool|int
    {
        return http_response_code($response_code);
    }

    /**
     * Add common security headers. It is advisable to always set this.
     *
     * @return static
     */
    public function addSecurityHeaders(): static
    {
        // Prevents browsers from MIME-sniffing a response away from the declared content type.
        $this->setResponseHeader('X-Content-Type-Options', 'nosniff');

        // Protects against clickjacking by controlling whether the browser should display the content in a frame.
        $this->setResponseHeader('X-Frame-Options', 'DENY');

        // Enables the Cross-Site Scripting (XSS) filter in the browser.
        $this->setResponseHeader('X-XSS-Protection', '1; mode=block');

        // Prevents Flash and other plugins from loading content from your site.
        $this->setResponseHeader('X-Permitted-Cross-Domain-Policies', 'none');

        // Referrer-Policy: Sends full URL on same-origin requests and only origin on cross-origin requests.
        $this->setResponseHeader('Referrer-Policy', 'no-referrer-when-downgrade');

        return $this;
    }

    /**
     * Add security headers for HTTPS (SSL) sites
     *
     * @return static
     */
    public function addSslSecurityHeaders(): static
    {
        // HSTS: Enforces secure (HTTPS) connections to the server.
        $this->setResponseHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Helps prevent misissued certificates by requiring Certificate Transparency
        $this->setResponseHeader('Expect-CT', 'max-age=86400, enforce');

        return $this;
    }

    /**
     * Set Content-Security-Policy (CSP)
     *
     * Mitigates XSS attacks by controlling the sources from which content (scripts, styles, images, etc.)
     * can be loaded. By default, only "self", but you can set addition nonces.
     *
     * @param array $nonces Can have 2 keys: script, style. Each one containing a sub-array of nonces to allow
     *
     * @return static
     */
    public function addCSPHeader(array $nonces = []): static
    {
        $scriptNonce = $nonces['script'] ?? 'default-script-nonce';
        $styleNonce = $nonces['style'] ?? 'default-style-nonce';

        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'nonce-$scriptNonce'; ";
        $csp .= "style-src 'self' 'nonce-$styleNonce'; ";

        $this->setResponseHeader('Content-Security-Policy', $csp);
        return $this;
    }

    /**
     * Set the Permissions-Policy (formerly Feature-Policy)
     *
     * Controls which web features and APIs can be used in the browser.
     *
     * @param bool $geolocation enable / disable (default) geolocation API
     * @param bool $microphone  enable / disable (default) microphone API
     * @param bool $camera      enable / disable (default) camera API
     *
     * @return static
     */
    public function addPermissionsPolicy(bool $geolocation = false, bool $microphone = false, bool $camera = false): static
    {
        $policy = [];

        if ($geolocation) {
            $policy[] = 'geolocation=(self)';
        } else {
            $policy[] = 'geolocation=()';
        }

        if ($microphone) {
            $policy[] = 'microphone=(self)';
        } else {
            $policy[] = 'microphone=()';
        }

        if ($camera) {
            $policy[] = 'camera=(self)';
        } else {
            $policy[] = 'camera=()';
        }

        $this->setResponseHeader('Permissions-Policy', implode(', ', $policy));
        return $this;
    }

    /**
     * Set the CORS (Cross Origin) headers
     *
     * @param string $allowOrigin wanted origin to allow: same-origin / cross-origin
     *
     * @return $this
     */
    public function addCORSHeaders(string $allowOrigin = 'same-origin'): static
    {
        if ($allowOrigin === 'cross-origin') {
            $this->setResponseHeader('Access-Control-Allow-Origin', '*');
            $this->setResponseHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $this->setResponseHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Authorization');
            $this->setResponseHeader('Access-Control-Allow-Credentials', 'true');
        } else {
            // Ensures that a document can only load resources from the same origin.
            $this->setResponseHeader('Access-Control-Allow-Origin', "'self'");
            $this->setResponseHeader('Cross-Origin-Embedder-Policy', 'require-corp');

            // Ensures that top-level navigation maintains isolation.
            $this->setResponseHeader('Cross-Origin-Opener-Policy', 'same-origin');
        }
        return $this;
    }

}