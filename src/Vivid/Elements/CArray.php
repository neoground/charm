<?php
/**
 * This file contains the CArray class.
 */

namespace Charm\Vivid\Elements;

use Illuminate\Support\Collection;

/**
 * Class CArray
 *
 * Charm Array - similar to the Collection of other frameworks
 *
 * @package Charm\Vivid\Elements
 */
class CArray
{
    protected array $array;
    protected Collection $collection;

    public function __construct(array $items = [])
    {
        $this->collection = new Collection($items);
        $this->array = $items;
    }

    public function hasMethod(string $method): bool
    {
        return method_exists($this, $method) || method_exists($this->collection, $method);
    }

    public function __call($method, $parameters)
    {
        if (method_exists($this->collection, $method)) {
            return call_user_func_array([$this->collection, $method], $parameters);
        }

        return false;
    }
}