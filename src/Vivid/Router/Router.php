<?php
/**
 * This file contains the Router class
 */

namespace Charm\Vivid\Router;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Handler;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\Kernel\Interfaces\OutputInterface;
use Charm\Vivid\Router\Attributes\Route;

/**
 * Class Router
 *
 * This class contains the routing for charm
 *
 * @package Charm\Vivid\Router
 */
class Router extends Module implements ModuleInterface
{
    /** @var Collector the route collector */
    protected $route;

    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // Nothing to do here yet.
    }

    /**
     * Init the routing system and collect all data
     *
     * This is called by the Handler after all modules are loaded because they might contain
     * sub-routes which need to be available first.
     *
     * @return true
     */
    public function init()
    {
        // Get router instance from cache
        if (C::AppStorage()->has('Routes', 'Collector')
            && C::AppStorage()->has('Routes', 'RoutesData')
        ) {
            $this->route = C::AppStorage()->get('Routes', 'Collector');
            return true;
        }

        $router = new Collector();

        // Require all route files so routes are collected
        $this->collectAllRoutes();

        // Get collected elements: filters, routes and groups
        $elements = [
            C::AppStorage()->get('Routes', 'Filters'),
            C::AppStorage()->get('Routes', 'Routes'),
            C::AppStorage()->get('Routes', 'Groups'),
        ];

        $routes = [];

        foreach ($elements as $element) {
            // Go through all elements and add them (if existing)
            if (is_array($element)) {
                foreach ($element as $el) {
                    $el->addToRouter($router, $routes);
                }
            }
        }

        $this->addAttributeRoutesToRouter($router, $routes);

        // Cache Collector instance + routes array
        C::AppStorage()->set('Routes', 'Collector', $router);
        C::AppStorage()->set('Routes', 'RoutesData', $routes);

        // Set for whole class
        $this->route = $router;
        return true;
    }

    /**
     * Add attribute routes if existing
     *
     * @param Collector $router
     * @param array     $routes
     */
    private function addAttributeRoutesToRouter($router, &$routes)
    {
        $attribute_routes = C::AppStorage()->get('Routes', 'AttributeRoutes');
        if (is_array($attribute_routes)) {
            /** @var Route $attr_route */
            foreach ($attribute_routes as $attr_route) {

                $method = $attr_route->method;
                $url = $attr_route->url;
                $name = $attr_route->name;

                $filter_before = $attr_route->filter_before;
                if (!is_array($filter_before)) {
                    $filter_before = [$filter_before];
                }

                $filter_after = $attr_route->filter_after;
                if (!is_array($filter_after)) {
                    $filter_after = [$filter_after];
                }

                $filter = ['before' => $filter_before, 'after' => $filter_after];
                $call_parts = [$attr_route->call_class, $attr_route->call_method];

                // Add to router
                $router->{$method}([$url, $name], $call_parts, $filter);

                // Add to routes array
                $routes[] = [
                    'method' => $method,
                    'url' => "/" . trim($url, "/"),
                    'name' => $name,
                    'call_class' => $attr_route->call_class,
                    'call_method' => $attr_route->call_method,
                    'filters' => $filter,
                ];
            }
        }
    }

    /**
     * Collect all routes
     */
    private function collectAllRoutes()
    {
        // Storage for attribute routes
        $attribute_routes = [];

        // Go through all modules
        $handler = Handler::getInstance();
        foreach ($handler->getModuleClasses() as $name => $module) {
            try {
                $mod = $handler->getModule($name);
                if (is_object($mod) && method_exists($mod, 'getBaseDirectory')) {
                    $dir = $mod->getBaseDirectory() . DS . 'Routes';

                    if (file_exists($dir)) {
                        // Add routes of this module
                        // And require them to collect routes, filters and groups defined in them
                        foreach (C::Storage()->scanDir($dir) as $file) {
                            require_once($dir . DS . $file);
                        }
                    }

                    // PHP8 Attribute routes
                    if (version_compare(phpversion(), '8.0', '>=')) {
                        $dir = $mod->getBaseDirectory() . DS . 'Controllers';

                        if (file_exists($dir)) {
                            $attribute_routes = $this->findAndAddAttributeRoutes($dir, $mod, $attribute_routes);
                        }
                    }
                }
            } catch (\Exception $e) {
                // If module throws error -> routes not needed
            }
        }

        if (count($attribute_routes) > 0) {
            C::AppStorage()->set('Routes', 'AttributeRoutes', $attribute_routes);
        }
    }

    /**
     * Find and add attribute routes in a directory with controller classes
     *
     * @param string $dir
     * @param Module $mod
     * @param array  $attribute_routes
     *
     * @return mixed
     * @throws \ReflectionException
     */
    private function findAndAddAttributeRoutes($dir, $mod, $attribute_routes)
    {
        foreach (C::Storage()->scanDir($dir) as $file) {

            if (is_dir($dir . DS . $file)) {
                // Check sub dir for files
                $attribute_routes = $this->findAndAddAttributeRoutes($dir . DS . $file, $mod, $attribute_routes);
            } else {
                // Got controller file
                // Get class name with namespace based on relative path in module
                $basedir = $mod->getBaseDirectory();
                $relpath = str_replace($basedir, '', $dir . DS . $file);
                $classname = str_replace(".php", "", $relpath);
                $classname = str_replace(DS, "\\", $classname);

                $refobject = new \ReflectionObject($mod);

                $class = $refobject->getNamespaceName() . $classname;

                if (class_exists($class)) {
                    $reflection = new \ReflectionClass($class);

                    foreach ($reflection->getMethods() as $method) {
                        $attributes = $method->getAttributes(Route::class);

                        foreach ($attributes as $attribute) {
                            $attr = $attribute->newInstance();
                            $attr->call_class = $class;
                            $attr->call_method = $method->getName();

                            // Add route
                            $attribute_routes[] = $attr;
                        }
                    }

                }

            }

        }

        return $attribute_routes;
    }

    /**
     * Build URL based on route
     *
     * If you pass an absolute URL the URL will be returned.
     *
     * TODO: Add support for controller + method name instead of route name
     *
     * @param string       $name name of route or absolute url
     * @param array|string $args (optional) array with values for all variables in route or single string for single
     *                           value
     *
     * @return string empty string if no route name was given
     */
    public function getUrl(string $name, array|string|null $args = []): string
    {
        if (empty($name)) {
            return '';
        }

        // Got URL?
        if (str_contains($name, '://')) {
            return $name;
        }

        // Remove whitespace
        $name = trim($name);

        // Query string
        $query_string = '';
        if (str_contains($name, '?')) {
            $parts = explode('?', $name);

            $name = array_shift($parts);
            $query_string = '?' . implode('?', $parts);
        }

        // Allow non array args if only one parameter
        if (!is_array($args)) {
            if (empty($args)) {
                $args = [];
            } else {
                $args = [$args];
            }
        }

        $route = $this->route->route($name, $args);
        return $this->getBaseUrl() . '/' . $route . $query_string;
    }

    /**
     * Build GET parameters to append to an URL
     *
     * This will return a string like ?foo=bar&a=b
     *
     * @param array $params    an array with all parameters
     * @param bool  $add_empty (opt.) also add parameter if value is empty? Default: false
     *
     * @return string
     */
    public function buildGetParameters(array $params, bool $add_empty = false): string
    {
        $str = '';

        foreach ($params as $k => $v) {

            if (!$add_empty) {
                if (!empty($v)) {
                    $str .= '&' . $k . '=' . $v;
                }
            } else {
                $str .= '&' . $k . '=' . $v;
            }

        }

        ltrim($str, '&');

        return '?' . $str;
    }

    /**
     * Check if a route exists
     *
     * @param string $name name of route
     *
     * @return bool
     */
    public function hasRoute(string $name): bool
    {
        return $this->route->hasRoute($name);
    }

    /**
     * Get base url relative to base directory
     *
     * E.g. url to project is http://charm.dev/clients/charm
     * So the relative url is /clients/charm
     *
     * @return string
     */
    public function getRelativeUrl(): string
    {
        $path_info = pathinfo($_SERVER['PHP_SELF']);
        return rtrim($path_info['dirname'], '/');
    }

    /**
     * Get base url without leading slash
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        if (array_key_exists('HTTP_HOST', $_SERVER)) {
            $protocol = C::Request()->isHttpsRequest() ? 'https://' : 'http://';
            return rtrim($protocol . $_SERVER['HTTP_HOST'] . $this->getRelativeUrl(), '/');
        }

        // No HTTP_HOST set, so probably CLI. Use base URL from config
        return rtrim(C::Config()->get('main:request.base_url'), '/');
    }

    /**
     * Get assets url without leading slash
     *
     * @return string
     */
    public function getAssetsUrl(): string
    {
        return $this->getBaseUrl() . '/assets';
    }

    /**
     * Get the current full url
     *
     * @param bool $includeParams include all query params in the URL?
     *
     * @return string
     */
    public function getCurrentUrl(bool $includeParams = true): string
    {
        $protocol = $_SERVER['HTTPS'] ??= 'off';
        $protocol = $protocol === 'on' ? 'https://' : 'http://';
        $url = $protocol . $_SERVER['HTTP_HOST'];

        if ($includeParams) {
            $url .= $_SERVER['REQUEST_URI'];
        } else {
            $url .= strtok($_SERVER['REQUEST_URI'], '?');
        }

        return $url;
    }


    /**
     * Get data of current route
     *
     * Return array includes these keys:
     *
     * name:    name of route
     * class:   called class
     * method:  called method
     * vars:    provided variables for method
     * filters: array with before and after filters
     *
     * @return array|bool array with data or false/null if not found
     */
    public function getCurrentRouteData(): mixed
    {
        return C::AppStorage()->get('Routes', 'CurrentRoute');
    }

    /**
     * Dispatch and call method
     *
     * @return OutputInterface the response
     */
    public function dispatch()
    {
        // Get relative url
        $relative_url = str_replace(
            $this->getRelativeUrl(),
            '',
            (string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
        );

        // Get route data from cache
        if (C::AppStorage()->has('Routes', 'RouteData')) {
            $data = C::AppStorage()->get('Routes', 'RouteData');
        } else {
            // Get data and cache it
            $data = $this->route->getData();
            C::AppStorage()->set('Routes', 'RouteData', $data);
        }

        $dispatcher = new Dispatcher($data);

        return $dispatcher->dispatch(
            $_SERVER['REQUEST_METHOD'],
            $relative_url
        );
    }

    /**
     * Get routes data array
     *
     * @return bool|array
     */
    public function getRoutesData()
    {
        return C::AppStorage()->get('Routes', 'RoutesData');
    }

    /**
     * Construct a URL
     *
     * @param string $url    the input URL, can include query parameters and more
     * @param array  $params an array with query parameters to add (key => value), will override existing ones
     *                       and append new ones
     *
     * @return string the new URL
     */
    public function constructUrl(string $url, array $params): string
    {
        $urlComponents = parse_url($url);

        $existingParams = [];
        if (isset($urlComponents['query'])) {
            parse_str($urlComponents['query'], $existingParams);
        }

        // Merge the new parameters with existing ones, overwriting if necessary
        $mergedParams = array_merge($existingParams, $params);

        // Build the new query string
        $urlComponents['query'] = http_build_query($mergedParams);

        // Recreate the URL
        return (isset($urlComponents['scheme']) ? $urlComponents['scheme'] . '://' : '')
            . (isset($urlComponents['user']) ? $urlComponents['user'] . (isset($urlComponents['pass']) ? ':' . $urlComponents['pass'] : '') . '@' : '')
            . ($urlComponents['host'] ?? '')
            . (isset($urlComponents['port']) ? ':' . $urlComponents['port'] : '')
            . ($urlComponents['path'] ?? '')
            . '?' . $urlComponents['query']
            . (isset($urlComponents['fragment']) ? '#' . $urlComponents['fragment'] : '');
    }

}