<?php
/**
 * This file contains the ViewExtender class
 */

namespace Charm\DebugBar;

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
        $twig->addFunction(new \Twig_Function('ViewExtenderTest', function () {
            return "HELLO EXTENDED WORLD!";
        }));
    }

    /**
     * Add data to <head> section
     *
     * @return string
     */
    public function addHeadData()
    {
        return Charm::DebugBar()->getRenderHead();
    }

    /**
     * Add data to end of <body> section
     */
    public function addBodyData()
    {
        return Charm::DebugBar()->getRenderBar();
    }

}