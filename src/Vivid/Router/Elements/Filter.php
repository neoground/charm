<?php
/**
 * This file contains the Filter element for Charm router
 */

namespace Charm\Vivid\Router\Elements;

use Charm\Vivid\C;
use Charm\Vivid\Router\RouterElement;
use Opis\Closure\SerializableClosure;

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
        C::AppStorage()->append('Routes', 'Filters', $filter);
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
     * @param array                           $routes data of all routes
     *
     * @return bool
     */
    public function addToRouter($router, &$routes)
    {
        $router->filter($this->name, $this->callback);
        return true;
    }

    /**
     * Sleep method
     *
     * Serialize closure
     */
    public function serialize()
    {
        $cb = $this->callback;

        if ($cb instanceof \Closure) {
            $s = new SerializableClosure($cb);
            $cb = $s->serialize();
        }

        return serialize([
            'name' => $this->name,
            'callback' => $cb,
        ]);
    }

    /**
     * Wakeup method
     *
     * Unserialize closure
     *
     * @param mixed $data
     */
    public function unserialize($data)
    {
        $us = unserialize($data);

        $this->name = $us['name'];

        if (!is_serialized($us['callback'])) {
            $this->callback = $us['callback'];
        } else {
            try {
                $this->callback = $us['callback']->getClosure($us['callback']);
            } catch (\Exception $e) {
                // Normal unserialize because super closure threw an error!
                $this->callback = unserialize($us['callback']);
            }
        }
    }

}