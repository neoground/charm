<?php
/**
 * This file contains the Route element for Charm router
 */

namespace Charm\Vivid\Router\Elements;

use Charm\Vivid\Charm;
use Charm\Vivid\Exceptions\LogicException;
use Charm\Vivid\Router\RouterElement;

/**
 * Class Route
 *
 * Defining a single route
 *
 * @package Charm\Vivid\Router\Elements
 */
class Route implements RouterElement
{
    /** @var null|string route name */
    protected $name;

    /** @var null|string http method */
    protected $method;

    /** @var null|string url */
    protected $url;

    /** @var null|string method to call */
    protected $call;

    /** @var null|array before / after filters */
    protected $filters;

    /**
     * Set name of route
     *
     * @param string $name
     *
     * @return Route
     */
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Prepend a string to the route name
     *
     * name=foo, $this->name=bar -> foo.bar
     *
     * @param string $name name to prepend
     *
     * @return Route
     */
    public function prependName($name)
    {
        $this->name = $name . '.' . $this->name;
        return $this;
    }

    /**
     * Set method
     *
     * @param string $method
     *
     * @return Route
     */
    public function method($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Set URL
     *
     * @param string $url
     *
     * @return Route
     */
    public function url($url)
    {
        $this->url = rtrim($url, '/');
        return $this;
    }

    /**
     * Prepend a string to the url
     *
     * url=foo, $this->url=bar -> foo/bar
     *
     * @param string $url url to prepend
     *
     * @return Route
     */
    public function prependUrl($url)
    {
        $this->url = rtrim($url, '/') . '/' . ltrim($this->url, '/');
        return $this;
    }

    /**
     * Set call controller / method
     *
     * @param mixed $call
     *
     * @return Route
     */
    public function call($call)
    {
        $this->call = $call;
        return $this;
    }

    /**
     * Set before filters
     *
     * @param array|string $filters array with filter names or single filter name
     *
     * @return Route
     */
    public function beforeFilters($filters)
    {
        $this->filters['before'] = $filters;
        return $this;
    }

    /**
     * Set after filters
     *
     * @param array|string $filters array with filter names or single filter name
     *
     * @return Route
     */
    public function afterFilters($filters)
    {
        $this->filters['after'] = $filters;
        return $this;
    }

    /**
     * Group constructor.
     *
     * @param null $name (optional) group name
     * @param bool $inside_group (optional) is route inside a group? Default: false
     */
    public function __construct($name = null, $inside_group = false)
    {
        $this->name = $name;
        $this->filters = [];

        if(!$inside_group) {
            Charm::AppStorage()->append('Routes', 'Routes', $this);
        }
    }

    /**
     * Create new route with GET method
     *
     * @param null|string $name (optional) route name
     *
     * @return Route
     */
    public static function get($name = null)
    {
        $route = new self($name);
        $route->method = 'get';

        return $route;
    }

    /**
     * Create new route with POST method
     *
     * @param null|string $name (optional) route name
     *
     * @return Route
     */
    public static function post($name = null)
    {
        $route = new self($name);
        $route->method = 'post';
        return $route;
    }

    /**
     * Create new route with PUT method
     *
     * @param null|string $name (optional) route name
     *
     * @return Route
     */
    public static function put($name = null)
    {
        $route = new self($name);
        $route->method = 'put';
        return $route;
    }

    /**
     * Create new route with DELETE method
     *
     * @param null|string $name (optional) route name
     *
     * @return Route
     */
    public static function delete($name = null)
    {
        $route = new self($name);
        $route->method = 'delete';
        return $route;
    }

    /**
     * Create new route with HEAD method
     *
     * @param null|string $name (optional) route name
     *
     * @return Route
     */
    public static function head($name = null)
    {
        $route = new self($name);
        $route->method = 'head';
        return $route;
    }

    /**
     * Create new route with PATCH method
     *
     * @param null|string $name (optional) route name
     *
     * @return Route
     */
    public static function patch($name = null)
    {
        $route = new self($name);
        $route->method = 'patch';
        return $route;
    }

    /**
     * Create new route with OPTIONS method
     *
     * @param null|string $name (optional) route name
     *
     * @return Route
     */
    public static function options($name = null)
    {
        $route = new self($name);
        $route->method = 'options';
        return $route;
    }

    /**
     * Create new route with any method
     *
     * @param null|string $name (optional) route name
     *
     * @return Route
     */
    public static function any($name = null)
    {
        $route = new self($name);
        $route->method = 'any';
        return $route;
    }

    /**
     * Create new route with dynamic controller call
     *
     * @param null|string $name (optional) route name
     *
     * @return Route
     */
    public static function controller($name = null)
    {
        $route = new self($name);
        $route->method = 'controller';
        return $route;
    }

    /**
     * Add element to router
     *
     * @param \Phroute\Phroute\RouteCollector $router
     *
     * @return bool
     *
     * @throws LogicException
     */
    public function addToRouter($router)
    {
        // Call: Controller.method
        $call_parts = explode(".", $this->call);

        if(count($call_parts) != 2) {
            throw new LogicException('Invalid controller call name: ' . $this->call);
        }

        if(!in_string("\\", $call_parts[0])) {
            // No namespace present. Prepend app namespace!
            $call_parts[0] = "\\App\\Controllers\\" . $call_parts[0];
        }

        $method = $this->method;
        $router->{$method}([$this->url, $this->name], $call_parts, $this->filters);

        return true;
    }

}