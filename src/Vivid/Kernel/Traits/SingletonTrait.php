<?php
/**
 * This file contains the singleton trait
 */

namespace Charm\Vivid\Kernel\Traits;

/**
 * Trait SingletonTrait
 *
 * Making a class a singleton
 *
 * @package Charm\Vivid\Kernel\Traits
 */
trait SingletonTrait
{
    /** @var \Singleton static instance */
    private static $instance;

    /**
     * Get instance
     *
     * @return \Singleton|self
     */
    final public static function getInstance()
    {
        return isset(static::$instance)
            ? static::$instance
            : static::$instance = new static;
    }

    /**
     * SingletonTrait constructor
     */
    final private function __construct()
    {
        $this->init();
    }

    /**
     * Class init (like constructor)
     */
    protected function init()
    {
    }

    /**
     * No cloning because of singleton
     */
    private function __clone()
    {
    }

}