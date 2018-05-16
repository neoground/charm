<?php
/**
 * This file contains the module interface
 */

namespace Charm\Vivid\Kernel\Interfaces;

/**
 * Interface ModuleInterface
 *
 * @package Charm\Vivid\Kernel\Interfaces
 */
interface ModuleInterface
{
    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule();

}