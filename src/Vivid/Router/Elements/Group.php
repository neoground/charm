<?php
/**
 * This file contains the Group element for Charm router
 */

namespace Charm\Vivid\Router\Elements;

use Charm\Vivid\C;
use Charm\Vivid\Router\RouterElement;

/**
 * Class Group
 *
 * Defining a route group
 *
 * @package Charm\Vivid\Router\Elements
 */
class Group implements RouterElement
{
    /** @var null|string route name */
    protected $name;

    /** @var null|string url */
    protected $url;

    /** @var null|Route[] routes inside this group */
    protected $routes;

    /** @var null|Group[] groups inside this group */
    protected $groups;

    /** @var null|array before / after filters */
    protected $filters;

    /**
     * Set name of route
     *
     * @param string $name
     *
     * @return Group
     */
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Prepend a string to the group name
     *
     * name=foo, $this->name=bar -> foo.bar
     *
     * @param string $name name to prepend
     *
     * @return Group
     */
    public function prependName($name)
    {
        $this->name = $name . '.' . $this->name;
        return $this;
    }

    /**
     * Set URL
     *
     * @param string $url
     *
     * @return Group
     */
    public function url($url)
    {
        $this->url = trim($url, '/');
        return $this;
    }

    /**
     * Prepend a string to the url
     *
     * url=foo, $this->url=bar -> foo/bar
     *
     * @param string $url url to prepend
     *
     * @return Group
     */
    public function prependUrl($url)
    {
        $this->url = trim($url, '/') . '/' . $this->url;
        return $this;
    }

    /**
     * Add a route inside this group
     *
     * @param string      $method method type (get, post, ...)
     * @param string|null $name   (optional) name of route
     *
     * @return Route
     */
    public function addRoute($method, $name = null)
    {
        $route = new Route($name, true);
        $route->method($method);

        $this->routes[] = $route;
        return $route;
    }

    /**
     * Add a group inside this group
     *
     * @param string $name (optional) name of sub-group
     *
     * @return Group
     */
    public function addGroup($name = null)
    {
        $group = new Group($name);
        $this->groups[] = $group;
        return $group;
    }

    /**
     * Add groups inside this group
     *
     * @param Group[] $groups
     *
     * @return Group
     */
    public function groups($groups)
    {
        $this->groups = $groups;
        return $this;
    }

    /**
     * Set before filters
     *
     * @param array|string $filters array with filter names or single filter name
     *
     * @return Group
     */
    public function beforeFilters($filters)
    {
        if(!is_array($filters)) {
            $filters = [$filters];
        }

        $this->filters['before'] = $filters;
        return $this;
    }

    /**
     * Set after filters
     *
     * @param array|string $filters array with filter names or single filter name
     *
     * @return Group
     */
    public function afterFilters($filters)
    {
        if(!is_array($filters)) {
            $filters = [$filters];
        }

        $this->filters['after'] = $filters;
        return $this;
    }

    /**
     * Set the full filters array (before / after)
     *
     * @param array $filters the filters array
     *
     * @return $this
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Route constructor.
     *
     * @param null $name
     * @param bool $inside_group (optional) is route inside a group? Default: false
     */
    public function __construct($name = null, $inside_group = false)
    {
        $this->name = $name;
        $this->filters = [];
        $this->routes = [];
        $this->groups = [];

        if(!$inside_group) {
            C::AppStorage()->append('Routes', 'Groups', $this);
        }
    }

    /**
     * Create new group
     *
     * @param null|string $name (optional) route name
     *
     * @return Group
     */
    public static function add($name = null)
    {
        $group = new self($name);

        return $group;
    }

    /**
     * Add element to router
     *
     * @param \Phroute\Phroute\RouteCollector $router
     * @param array $routes data of all routes
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function addToRouter($router, &$routes)
    {
        // Groups won't be added to the router itself.
        // Only routes and routes of sub-groups.

        // Go through all sub-routes, prepend name + url, add them
        foreach($this->routes as $route) {
            $route->prependUrl($this->url);
            $route->prependName($this->name);
            $route->setFilters($this->filters);
            $route->addToRouter($router, $routes);
        }

        // Go through all sub-groups, prepend name + url, add them
        foreach($this->groups as $group) {
            $group->prependUrl($this->url);
            $group->prependName($this->name);
            $group->setFilters($this->filters);
            $group->addToRouter($router, $routes);
        }

        return true;
    }

}