<?php
/**
 * This file contains the Events module.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Charm;
use Charm\Vivid\Exceptions\ModuleNotFoundException;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Events
 *
 * Events module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Events implements ModuleInterface
{
    /**
     * Load the module
     */
    public function loadModule()
    {
        // Nothing to do here yet.
    }

    /**
     * @param string $module name of module
     * @param string $name   name of event
     * @param string $method method to call
     *
     * @return bool
     *
     * @throws ModuleNotFoundException
     */
    public function addListener($module, $name, $method)
    {
        if(!Charm::has($module)) {
            throw new ModuleNotFoundException('Module ' . $module . ' could not be found.');
        }

        // Add listener
        return Charm::AppStorage()->append('Events', $module . '_' . $name, $method);
    }

    /**
     * Get all listeners of an event
     *
     * @param string $module name of module
     * @param string $name   name of event
     *
     * @return false|null|array
     */
    public function getListeners($module, $name)
    {
        return Charm::AppStorage()->get('Events', $module . '_' . $name);
    }

    /**
     * Fire an event and call all listeners
     *
     * @param string $module name of module
     * @param string $name   name of event
     *
     * @return bool
     */
    public function fireEvent($module, $name)
    {
        $listeners = $this->getListeners($module, $name);

        $ret = true;

        if(is_array($listeners) && count($listeners) > 0) {
            foreach($listeners as $listener) {
                // Call method
                $ret &= call_user_func($listener);
            }
        }

        return $ret;
    }

}