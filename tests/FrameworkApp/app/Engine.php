<?php
/**
 * This file contains the basic configuration.
 */

namespace App;

use Charm\Vivid\Kernel\EngineManager;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

class Engine extends EngineManager implements ModuleInterface
{
    /**
     * Engine constructor.
     */
    public function __construct()
    {
        $this->setEnvironment('Test');
    }

    /**
     * Load the module
     */
    public function loadModule()
    {

    }
}