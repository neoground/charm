<?php
/**
 * This file contains the Dispatcher class
 */

namespace Charm\Vivid\Router;

use Charm\Vivid\Charm;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\HandlerResolver;
use Phroute\Phroute\HandlerResolverInterface;
use Phroute\Phroute\Route;
use Phroute\Phroute\RouteDataInterface;

/**
 * Class Dispatcher
 *
 * Custom PHRoute dispatcher
 *
 * @package Charm\Vivid\Router
 */
class Dispatcher {

    private $staticRouteMap;
    private $variableRouteData;
    private $filters;
    private $handlerResolver;
    public $matchedRoute;

    /**
     * Create a new route dispatcher.
     *
     * @param RouteDataInterface $data
     * @param HandlerResolverInterface $resolver
     */
    public function __construct(RouteDataInterface $data, HandlerResolverInterface $resolver = null)
    {
        $this->staticRouteMap = $data->getStaticRoutes();

        $this->variableRouteData = $data->getVariableRoutes();

        $this->filters = $data->getFilters();

        if ($resolver === null)
        {
            $this->handlerResolver = new HandlerResolver();
        }
        else
        {
            $this->handlerResolver = $resolver;
        }
    }

    /**
     * Dispatch a route for the given HTTP Method / URI.
     *
     * @param $httpMethod
     * @param $uri
     *
     * @return mixed|null
     *
     * @throws HttpMethodNotAllowedException
     * @throws HttpRouteNotFoundException
     */
    public function dispatch($httpMethod, $uri)
    {
        list($handler, $filters, $vars) = $this->dispatchRoute($httpMethod, trim($uri, '/'));

        list($beforeFilter, $afterFilter) = $this->parseFilters($filters);

        // Save route meta data
        $route_name = null;
        $all_routes = Charm::AppStorage()->get('Routes', 'RoutesData');
        foreach($all_routes as $route) {
            if($route['call_class'] == $handler[0] && $route['call_method'] == $handler[1]) {
                $route_name = $route['name'];
                break;
            }
        }

        $data = [
            'name' => $route_name,
            'class' => $handler[0],
            'method' => $handler[1],
            'vars' => $vars,
            'filters' => [
                'before' => $beforeFilter,
                'after' => $afterFilter
            ]
        ];
        Charm::AppStorage()->set('Routes', 'CurrentRoute', $data);

        if(($response = $this->dispatchFilters($beforeFilter)) !== null)
        {
            return $response;
        }

        $resolvedHandler = $this->handlerResolver->resolve($handler);

        $response = call_user_func_array($resolvedHandler, $vars);

        return $this->dispatchFilters($afterFilter, $response);
    }

    /**
     * Dispatch a route filter.
     *
     * @param $filters
     * @param null $response
     *
     * @return mixed|null
     */
    private function dispatchFilters($filters, $response = null)
    {
        while($filter = array_shift($filters))
        {
            // Find optional parameters
            $parts = explode(":", $filter);
            $param = $response;
            if(count($parts) > 1) {
                $filter = array_shift($parts);
                $param = implode(":", $parts);
            }

            // Get and call handler
            $handler = $this->handlerResolver->resolve($filter);
            if(($filteredResponse = call_user_func($handler, $param)) !== null)
            {
                return $filteredResponse;
            }
        }

        return $response;
    }

    /**
     * Normalise the array filters attached to the route and merge with any global filters.
     *
     * @param $filters
     * @return array
     */
    private function parseFilters($filters)
    {
        $beforeFilter = array();
        $afterFilter = array();

        if(isset($filters[Route::BEFORE]))
        {
            $beforeFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::BEFORE]));
        }

        if(isset($filters[Route::AFTER]))
        {
            $afterFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::AFTER]));
        }

        return array($beforeFilter, $afterFilter);
    }

    /**
     * Perform the route dispatching. Check static routes first followed by variable routes.
     *
     * @param $httpMethod
     * @param $uri
     *
     * @return mixed
     *
     * @throws HttpMethodNotAllowedException
     * @throws HttpRouteNotFoundException
     */
    private function dispatchRoute($httpMethod, $uri)
    {
        if (isset($this->staticRouteMap[$uri]))
        {
            return $this->dispatchStaticRoute($httpMethod, $uri);
        }

        return $this->dispatchVariableRoute($httpMethod, $uri);
    }

    /**
     * Handle the dispatching of static routes.
     *
     * @param $httpMethod
     * @param $uri
     *
     * @return mixed
     *
     * @throws HttpMethodNotAllowedException
     */
    private function dispatchStaticRoute($httpMethod, $uri)
    {
        $routes = $this->staticRouteMap[$uri];

        if (!isset($routes[$httpMethod]))
        {
            $httpMethod = $this->checkFallbacks($routes, $httpMethod);
        }

        return $routes[$httpMethod];
    }

    /**
     * Check fallback routes: HEAD for GET requests followed by the ANY attachment.
     *
     * @param $routes
     * @param $httpMethod
     *
     * @return mixed
     *
     * @throws HttpMethodNotAllowedException
     */
    private function checkFallbacks($routes, $httpMethod)
    {
        $additional = array(Route::ANY);

        if($httpMethod === Route::HEAD)
        {
            $additional[] = Route::GET;
        }

        foreach($additional as $method)
        {
            if(isset($routes[$method]))
            {
                return $method;
            }
        }

        $this->matchedRoute = $routes;

        throw new HttpMethodNotAllowedException('Allow: ' . implode(', ', array_keys($routes)));
    }

    /**
     * Handle the dispatching of variable routes.
     *
     * @param $httpMethod
     * @param $uri
     *
     * @return mixed
     *
     * @throws HttpMethodNotAllowedException
     * @throws HttpRouteNotFoundException
     */
    private function dispatchVariableRoute($httpMethod, $uri)
    {
        foreach ($this->variableRouteData as $data)
        {
            if (!preg_match($data['regex'], $uri, $matches))
            {
                continue;
            }

            $count = count($matches);

            while(!isset($data['routeMap'][$count++]));

            $routes = $data['routeMap'][$count - 1];

            if (!isset($routes[$httpMethod]))
            {
                $httpMethod = $this->checkFallbacks($routes, $httpMethod);
            }

            foreach (array_values($routes[$httpMethod][2]) as $i => $varName)
            {
                if(!isset($matches[$i + 1]) || $matches[$i + 1] === '')
                {
                    unset($routes[$httpMethod][2][$varName]);
                }
                else
                {
                    $routes[$httpMethod][2][$varName] = $matches[$i + 1];
                }
            }

            return $routes[$httpMethod];
        }

        throw new HttpRouteNotFoundException('Route ' . $uri . ' does not exist');
    }
}
