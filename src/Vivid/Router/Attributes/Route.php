<?php
/**
 * This file contains the Route attribute class
 */

namespace Charm\Vivid\Router\Attributes;

/**
 * Class Route
 *
 * Route attribute class
 *
 * @package Charm\Vivid\Router
 */
class Route {
    public string $method;
    public string $url;
    public string $name;
    public string $filter_before;
    public string $filter_after;
    public string $call_class;
    public string $call_method;

    public function __construct(string $method,
                                string $url,
                                string $name = '',
                                string $filter_before = '',
                                string $filter_after = '')
    {
        $this->method = $method;
        $this->url = $url;
        $this->name = $name;
        $this->filter_before = $filter_before;
        $this->filter_after = $filter_after;
    }
}