<?php
/**
 * This file contains the ViewExtender class
 */

namespace Charm\Guard;

use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\ViewExtenderInterface;

/**
 * Class ViewExtender
 *
 * Extending the view
 *
 * @package Charm\DebugBar
 */
class ViewExtender implements ViewExtenderInterface
{
    /**
     * ViewExtender constructor.
     */
    public function __construct()
    {

    }

    /**
     * Add debugBar functions to twig
     *
     * @param $twig \Twig_Environment the twig instance
     */
    public function extendTwig(&$twig)
    {
        // Get currently logged in user
        $twig->addFunction(new \Twig_Function('getCurrentUser', function () {
            return Charm::Guard()->getUser();
        }));

        // Is user logged in?
        $twig->addFunction(new \Twig_Function('isLoggedIn', function () {
            return Charm::Guard()->isLoggedIn();
        }));
    }

    /**
     * Add data to <head> section
     *
     * @return string
     */
    public function addHeadData()
    {
        return '';
    }

    /**
     * Add data to end of <body> section
     */
    public function addBodyData()
    {
        return '';
    }

}