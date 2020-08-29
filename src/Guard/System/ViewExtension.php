<?php
/**
 * This file contains the ViewExtension class
 */

namespace Charm\Guard\System;

use App\Models\File;
use App\Models\Note;
use App\Models\Notification;
use App\Models\Setting;
use Carbon\Carbon;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Handler;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class ViewExtension
 *
 * Adding view functions to twig views and much more!
 *
 * @package Charm\Guard\System
 */
class ViewExtension extends AbstractExtension
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
            'getOperators',
            'getAdminMenu'
        ];

        $arr = [];

        // Build array, remove twig methods
        foreach ($methods as $method) {
            if (!in_array($method, $ignore)) {
                $arr[$method] = new TwigFunction($method, [$this, $method]);
            }
        }

        return $arr;
    }

    /**
     * Get the logged in user
     *
     * @return object|false  the user object or false if guard is disabled
     */
    public function getCurrentUser()
    {
        return C::Guard()->getUser();
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        return C::Guard()->isLoggedIn();
    }

}