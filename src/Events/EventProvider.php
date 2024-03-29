<?php
/**
 * This file contains the EventProvider class
 */

namespace Charm\Events;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
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
                        foreach (C::Storage()->scanDir($dir) as $file) {
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
     */
    public function addListener(string $module, string $name, string $method): bool
    {
        // Add listener
        return C::AppStorage()->append('Events', $module . '_' . $name, $method);
    }

    /**
     * Get all listeners of an event
     *
     * @param string $module name of module
     * @param string $name   name of event
     *
     * @return array
     */
    public function getListeners(string $module, string $name): array
    {
        $listeners = C::AppStorage()->get('Events', $module . '_' . $name);
        return (empty($listeners) ? [] : $listeners);
    }

    /**
     * Fire an event and call all listeners
     *
     * @param string $module name of module
     * @param string $name   name of event
     * @param mixed  $args   optional argument to pass to fire methods
     *
     * @return bool
     */
    public function fire(string $module, string $name, mixed $args = null): bool
    {
        $listeners = $this->getListeners($module, $name);

        $ret = true;

        if(count($listeners) > 0) {
            foreach($listeners as $listener) {
                // Call method
                if(str_contains($listener, "::")) {
                    // Static method
                    $ret &= call_user_func($listener, $args);
                } else {
                    // Normal method -> init class + call method
                    $parts = explode(".", $listener);
                    if(count($parts) === 2 && class_exists($parts[0])) {
                        $ret &= call_user_func([new $parts[0], $parts[1]], $args);
                    }
                }

            }
        }

        return (bool) $ret;
    }
}