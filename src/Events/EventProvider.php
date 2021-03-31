<?php
/**
 * This file contains the EventProvider class
 */

namespace Charm\Events;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;
use Charm\Vivid\Exceptions\ModuleNotFoundException;
use Charm\Vivid\Kernel\Handler;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class EventProvider
 *
 * Module binding to Charm kernel
 *
 * @package Charm\Events
 */
class EventProvider extends Module implements ModuleInterface
{
    /**
     * Load the module
     */
    public function loadModule()
    {
        // Nothing to do here yet.
    }

    /**
     * Post init hook
     *
     * Will add all events from all modules
     */
    public function postInit()
    {
        // Add all events
        foreach(Handler::getInstance()->getModuleClasses() as $name => $module) {
            try {
                $mod = Handler::getInstance()->getModule($name);
                if(is_object($mod) && method_exists($mod, 'getReflectionClass')) {

                    // Check if events exist for this module
                    $dir = $mod->getBaseDirectory() . DS . 'System' . DS . 'EventListener';
                    $namespace = $mod->getReflectionClass()->getNamespaceName() . "\\System\\EventListener";

                    if(file_exists($dir)) {
                        // Add all events in this directory
                        $files = array_diff(scandir($dir), ['..', '.']);

                        // Go through all files
                        foreach ($files as $file) {
                            $fullpath = $dir . DS . $file;
                            $pathinfo = pathinfo($fullpath);
                            require_once($fullpath);

                            $class = $namespace . "\\" . $pathinfo['filename'];

                            if (class_exists($class)) {
                                $instance = new $class;
                                $instance->addEvent();
                            }
                        }

                    }
                }
            } catch (\Exception $e) {
                // Console command not existing?
                // Just continue...
            }
        }
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
    public function fire($module, $name)
    {
        $listeners = $this->getListeners($module, $name);

        $ret = true;

        if(is_array($listeners) && count($listeners) > 0) {
            foreach($listeners as $listener) {
                // Call method

                if(in_string("::", $listener)) {
                    // Static method
                    $ret &= call_user_func($listener);
                } else {
                    // Normal method -> init class + call method
                    $parts = explode(".", $listener);
                    if(count($parts) === 2 && class_exists($parts[0])) {
                        $ret &= call_user_func([new $parts[0], $parts[1]]);
                    }
                }

            }
        }

        return $ret;
    }
}