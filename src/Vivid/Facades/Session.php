<?php

namespace Charm\Vivid\Facades;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;

/**
 * Session facade
 *
 * Quick access to C::Session()
 */
class Session
{
    /**
     * Call a static method on the underlying module instance.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        // Access the module instance and call the method dynamically
        return call_user_func_array(
            [self::getModule(), $method],
            $arguments
        );
    }

    /**
     * Get the module instance.
     *
     * @return Module
     */
    protected static function getModule(): Module
    {
        return C::Session();
    }
}
