<?php

namespace Charm\Vivid\Facades;

use Charm\Vivid\C;
use Illuminate\Database\Connection;

/**
 * Database facade
 *
 * Quick access to C::Database()->getDatabaseConnection()
 */
class DB
{
    /**
     * Call a static method on the underlying database connection.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        // Access the database connection and call the method dynamically
        return call_user_func_array(
            [self::getDatabaseConnection(), $method],
            $arguments
        );
    }

    /**
     * Get the database connection from the main framework's C class.
     *
     * @return Connection
     */
    protected static function getDatabaseConnection(): Connection
    {
        return C::Database()->getDatabaseConnection();
    }
}
