<?php
/**
 * This file contains the Route attribute class
 */

namespace Charm\Vivid\Router\Attributes;

/**
 * Attribute class Route
 *
 * Route attribute class
 *
 * @package Charm\Vivid\Router
 */
#[\Attribute]
class Route {
    public string $method;
    public string $url;
    public string $name;
    public string|array $filter_before;
    public string|array $filter_after;
    public string $call_class;
    public string $call_method;

    public function __construct(string $method,
                                string $url,
                                string $name = '',
                                string|array $filter_before = '',
                                string|array $filter_after = '')
    {
        $this->method = $method;
        $this->url = $url;
        $this->name = $name;
        $this->filter_before = $filter_before;
        $this->filter_after = $filter_after;
    }
}