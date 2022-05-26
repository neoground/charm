<?php
/**
 * This file contains the init class for database.
 */

namespace Charm\Database;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Helper\EloquentDebugbar;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;

/**
 * Class Database
 *
 * Database module
 */
class Database extends Module implements ModuleInterface
{
    /** @var Connection the eloquent database connection */
    protected Connection $eloquent_instance;

    /**
     * Module init
     */
    public function loadModule()
    {
        $db_available = false;

        // Init Eloquent
        $capsule = new Manager;

        // Add single database (default)
        $config = C::Config()->get('connections:database');

        if(is_array($config) && $config['enabled']) {
            unset($config['enabled']);

            $capsule->addConnection($config);
            $db_available = true;
        }

        // Add multiple databases
        $config = C::Config()->get('connections:databases');

        if(is_array($config)) {
            foreach($config as $name => $dbvals) {
                if(is_array($dbvals) && $dbvals['enabled']) {
                    // Got valid entry
                    unset($dbvals['enabled']);

                    $capsule->addConnection($dbvals, $name);
                    $db_available = true;
                }
            }
        }

        // Finish setup if at least 1 database is present
        if($db_available) {
            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            // Make capsule accessible
            $this->eloquent_instance = $capsule->getConnection();

            // Add to debug bar
            $this->addEloquentToDebugBar();
        }
    }

    /**
     * Add eloquent queries to debug bar
     */
    private function addEloquentToDebugBar()
    {
        if(C::has('DebugBar')) {
            $debugbar = C::DebugBar()->getInstance();

            // Add Eloquent data if we got a database connection
            if (C::Config()->get('main:debug.debugmode', false)
                && C::Config()->get('main:debug.show_debugbar', false)) {
                // Init and add debug bar collector
                $debugbar->addCollector(new EloquentDebugbar());
            }
        }
    }

    /**
     * Get the database connection
     *
     * @return Connection
     */
    public function getDatabaseConnection()
    {
        return $this->eloquent_instance;
    }

    /**
     * Create a database dump
     *
     * @param string $file absolute path to file in which the dump will be stored
     * @param string $tables optional table name, 'all' for full database (default)
     * @param string $connection optional connection name
     *
     * @return bool true on success, false on failure
     */
    public function createDump(string $file, string $tables = 'all', string $connection = 'default') : bool
    {
        // TODO Add logic with symfony/process and native mysqldump
        // TODO Also add option to backup a single table
        return true;
    }

}