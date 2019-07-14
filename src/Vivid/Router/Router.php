<?php
/**
 * This file contains the Router class
 */

namespace Charm\Vivid\Router;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;
use Charm\Vivid\Exceptions\LogicException;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\Kernel\Interfaces\OutputInterface;
use Charm\Vivid\PathFinder;
use Phroute\Phroute\RouteCollector;

/**
 * Class Router
 *
 * This class contains the routing for charm
 *
 * @package Charm\Vivid\Router
 */
class Router extends Module implements ModuleInterface
{
    /** @var RouteCollector the route collector */
    protected $route;

    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // Collect routes and init everything
        $this->init();
    }

    /**
     * Init the routing system and collect all data
     *
     * @return true
     */
    private function init()
    {
        // Get router instance from cache
        if (Charm::AppStorage()->has('Routes', 'RouteCollector')
            && Charm::AppStorage()->has('Routes', 'RoutesData')
        ) {
            $this->route = Charm::AppStorage()->get('Routes', 'RouteCollector');
            $this->routes = Charm::AppStorage()->get('Routes', 'RoutesData');
            return true;
        }

        $router = new RouteCollector();

        // Require all route files so routes are collected
        $this->collectAllRoutes();

        // Get collected elements: filters, routes and groups
        $elements = [
            Charm::AppStorage()->get('Routes', 'Filters'),
            Charm::AppStorage()->get('Routes', 'Routes'),
            Charm::AppStorage()->get('Routes', 'Groups')
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

        // Cache RouteCollector instance + routes array
        Charm::AppStorage()->set('Routes', 'RouteCollector', $router);
        Charm::AppStorage()->set('Routes', 'RoutesData', $routes);

        // Set for whole class
        $this->route = $router;
        return true;
    }

    /**
     * Collect all routes
     */
    private function collectAllRoutes()
    {
        $dir = PathFinder::getAppPath() . DS . 'Routes';

        // Get all files without dotfiles
        $files = array_slice(scandir($dir), 2);

        // And require them to collect routes, filters and groups defined in them
        foreach ($files as $file) {
            require_once($dir . DS . $file);
        }
    }

    /**
     * Build URL based on route
     *
     * If you pass an absolute URL the URL will be returned.
     *
     * @param string $name name of route or absolute url
     * @param array|string $args (optional) array with values for all variables in route
     *
     * @return string
     *
     * @throws LogicException if no route name is given
     */
    public function buildUrl(string $name, array $args = []) : string
    {
        if(empty($name)) {
            throw new LogicException('No route name given');
        }

        // Got URL?
        if (in_string('://', $name)) {
            return $name;
        }

        // Remove whitespace
        $name = trim($name);

        // Query string
        $query_string = '';
        if (in_string('?', $name)) {
            $parts = explode('?', $name);

            $name = array_shift($parts);
            $query_string = '?' . implode('?', $parts);
        }

        // Allow non array args if only one parameter
        if (!is_array($args)) {
            $args = [$args];
        }

        $route = $this->route->route($name, $args);
        return $this->getBaseUrl() . '/' . $route . $query_string;
    }

    /**
     * Build GET parameters to append to an URL
     *
     * This will return a string like ?foo=bar&a=b
     *
     * @param array $params           an array with all parameters
     * @param bool  $add_empty (opt.) also add parameter if value is empty? Default: false
     *
     * @return string
     */
    public function buildGetParameters(array $params, bool $add_empty = false) : string
    {
        $str = '';

        foreach($params as $k => $v) {

            if(!$add_empty) {
                if(!empty($v)) {
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
    public function hasRoute(string $name) : bool
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
    public function getRelativeUrl() : string
    {
        $path_info = pathinfo($_SERVER['PHP_SELF']);
        return rtrim($path_info['dirname'], '/');
    }

    /**
     * Get base url without leading slash
     *
     * @return string
     */
    public function getBaseUrl() : string
    {
        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        return rtrim($protocol . $_SERVER['HTTP_HOST'] . $this->getRelativeUrl(), '/');
    }

    /**
     * Get assets url without leading slash
     *
     * @return string
     */
    public function getAssetsUrl() : string
    {
        return $this->getBaseUrl() . '/assets';
    }

    /**
     * Get the current full url
     *
     * @return string
     */
    public function getCurrentUrl() : string
    {
        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
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
     * @return array
     */
    public function getCurrentRouteData() : array
    {
        return Charm::AppStorage()->get('Routes', 'CurrentRoute');
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
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
        );

        // Get route data from cache
        if (Charm::AppStorage()->has('Routes', 'RouteData')) {
            $data = Charm::AppStorage()->get('Routes', 'RouteData');
        } else {
            // Get data and cache it
            $data = $this->route->getData();
            Charm::AppStorage()->set('Routes', 'RouteData', $data);
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
        return Charm::AppStorage()->get('Routes', 'RoutesData');
    }

}