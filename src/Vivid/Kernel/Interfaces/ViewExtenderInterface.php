<?php
/**
 * This file contains the ViewExtender interface
 */

namespace Charm\Vivid\Kernel\Interfaces;

/**
 * Interface ViewExtenderInterface
 *
 * @package Charm\Vivid\Kernel\Interfaces
 */
interface ViewExtenderInterface
{

    /**
     * ViewExtender constructor.
     */
    public function __construct();

    /**
     * Extend twig
     *
     * @param $twig \Twig_Environment the twig instance
     */
    public function extendTwig(&$twig);

    /**
     * Add data to <head> section
     *
     * @return string
     */
    public function addHeadData();

    /**
     * Add data to end of <body> section
     */
    public function addBodyData();

}