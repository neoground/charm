<?php
/**
 * This file contains the Event class
 */

namespace Charm\Events;

use Charm\Vivid\Charm;
use Charm\Vivid\Exceptions\LogicException;
use Charm\Vivid\Exceptions\ModuleNotFoundException;

/**
 * Class Event
 *
 * The base event which should be extended by all events.
 *
 * @package Charm\Events
 */
class Event
{
    /** @var string name of according module */
    protected $module;

    /** @var string name of event */
    protected $name;

    /**
     * Event constructor.
     */
    public function __construct()
    {
        $this->configure();
    }

    /**
     * Configures the current event
     */
    protected function configure()
    {

    }

    /**
     * Firing the event
     *
     * @throws LogicException When the fire() method is not implemented in an event
     */
    protected function fire()
    {
        throw new LogicException("You must override the fire() method for this event.");
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set event name
     *
     * @param string $name
     *
     * @return self
     */
    private function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get module name
     *
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set module name
     *
     * @param string $module
     *
     * @return self
     */
    private function setModule($module)
    {
        $this->module = $module;
        return $this;
    }

    /**
     * Easy configuration method
     *
     * @param string $module name of module
     * @param string $name   name of event when this should be fired
     */
    private function fireOnEvent($module, $name)
    {
        $this->setModule($module)->setName($name);
    }

    /**
     * Add this event to the charm event handler
     *
     * @throws ModuleNotFoundException
     */
    public function addEvent()
    {
        Charm::Events()->addListener($this->module, $this->name, self::class . '.fire');
    }


}