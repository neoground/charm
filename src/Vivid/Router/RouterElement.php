<?php
/**
 * This file contains the RouterElement interface
 */

namespace Charm\Vivid\Router;

/**
 * Interface RouterElement
 *
 * Defining a router element
 *
 * @package Charm\Vivid\Router
 */
interface RouterElement
{
    /**
     * Add element to router
     *
     * @param \Phroute\Phroute\RouteCollector $router
     *
     * @return bool
     */
    public function addToRouter($router);
}