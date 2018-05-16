<?php
/**
 * This file contains the Supervisor class
 */

namespace Charm\Supervisor;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Supervisor
 *
 * Module binding to Charm kernel
 *
 * @package Charm\Guard
 */
class Supervisor extends Module implements ModuleInterface
{
    protected $user_class;

    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // Nothing to do here yet.
    }

}