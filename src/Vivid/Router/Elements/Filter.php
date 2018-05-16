<?php
/**
 * This file contains the Filter element for Charm router
 */

namespace Charm\Vivid\Router\Elements;

use Charm\Vivid\Charm;
use Charm\Vivid\Router\RouterElement;

/**
 * Class Filter
 *
 * Defining a route filter
 *
 * @package Charm\Vivid\Router\Elements
 */
class Filter implements RouterElement
{
    protected $name;

    protected $callback;

    /**
     * Create new filter
     *
     * @param string   $name     filter name
     * @param callable $callback filter function
     */
    public static function add($name, $callback)
    {
        $filter = new self($name, $callback);
        Charm::AppStorage()->append('Routes', 'Filters', $filter);
    }

    /**
     * Filter constructor
     *
     * @param string   $name     filter name
     * @param callable $callback filter function
     */
    public function __construct($name, $callback)
    {
        $this->name = $name;
        $this->callback = $callback;
    }

    /**
     * Add element to router
     *
     * @param \Phroute\Phroute\RouteCollector $router
     *
     * @return bool
     */
    public function addToRouter($router)
    {
        $router->filter($this->name, $this->callback);
        return true;
    }

}