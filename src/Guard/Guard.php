<?php
/**
 * This file contains the Module class
 */

namespace Charm\Guard;

use App\Models\User;
use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Module
 *
 * Module binding to Charm kernel
 *
 * @package Charm\Guard
 */
class Guard extends Module implements ModuleInterface
{
    protected $user_class;

    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // Get user class
        $this->user_class = Charm::App()->getConfig('user_class');
    }

    /**
     * Get the logged in user
     *
     * @return User
     */
    public function getUser()
    {
        // TODO: Add real implementation
        $class = $this->user_class;
        return $class::find(1);
    }
}