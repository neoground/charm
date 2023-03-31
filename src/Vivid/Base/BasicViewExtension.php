<?php
/**
 * This file contains the BasicViewExtension class.
 */

namespace Charm\Vivid\Base;


use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class BasicViewExtension
 *
 * Base view extension class
 *
 * @package Charm\Vivid\Base
 */
class BasicViewExtension extends AbstractExtension
{
    /**
     * Set array of all functions to add to twig
     *
     * @return array|TwigFunction[]
     */
    public function getFunctions()
    {
        // Get all functions in this class
        $methods = get_class_methods($this);

        // Methods to ignore (from parent)
        $ignore = [
            'getTokenParsers',
            'getNodeVisitors',
            'getFilters',
            'getTests',
            'getFunctions',
            'getOperators'
        ];

        $arr = [];

        // Build array, ignore twig methods
        foreach($methods as $method) {
            if(!in_array($method, $ignore)) {
                $arr[$method] = new TwigFunction($method, [$this, $method]);
            }
        }

        return $arr;
    }

}