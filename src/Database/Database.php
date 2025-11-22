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
    /** @var Connection[] the eloquent database connections by name */
    protected array $eloquent_instances = [];

    /**
     * Module init
     */
    public function loadModule()
    {
        $db_available = false;

        // Init Eloquent
        $capsule = new Manager;

        // Add single database (default)
        $connections = [];
        $config = C::Config()->get('connections:database');

        if (is_array($config) && $config['enabled']) {
            unset($config['enabled']);

            $capsule->addConnection($config);
            $connections[] = 'default';
            $db_available = true;
        }

        // Add multiple databases
        $config = C::Config()->get('connections:databases');

        if (is_array($config)) {
            foreach ($config as $name => $dbvals) {
                if (is_array($dbvals) && $dbvals['enabled']) {
                    // Got valid entry
                    unset($dbvals['enabled']);

                    $capsule->addConnection($dbvals, $name);
                    $connections[] = $name;
                    $db_available = true;
                }
            }
        }

        // Finish setup if at least 1 database is present
        if ($db_available) {
            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            // Make capsule accessible
            foreach ($connections as $name) {
                $this->eloquent_instances[$name] = $capsule->getConnection($name);
            }

            // Add to debug bar
            $this->addEloquentToDebugBar();
        }
    }

    /**
     * Add eloquent queries to debug bar
     */
    private function addEloquentToDebugBar(): void
    {
        if (C::has('DebugBar') && C::DebugBar()->isEnabled()) {
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
     * @param string $name connection name to get (default: "default")
     *
     * @return Connection|null the connection or null if not available / existing
     */
    public function getDatabaseConnection(string $name = 'default'): ?Connection
    {
        return $this->eloquent_instances[$name] ?? null;
    }

    /**
     * Get an array of all available databases
     *
     * @return array
     */
    public function getAllDatabases(): array
    {
        $dbs = $this->getDatabaseConnection()->select('SHOW DATABASES');
        $db_list = [];
        foreach ($dbs as $db) {
            $db_list[] = $db->Database;
        }
        return $db_list;
    }

    /**
     * Get an array of all available tables from database
     *
     * @return array
     */
    public function getAllTables(): array
    {
        $tables = $this->getDatabaseConnection()->select('SHOW TABLES');
        $key = 'Tables_in_' . C::Config()->get('connections:database.database');

        $arr = [];
        foreach ($tables as $table) {
            $arr[] = $table->$key;
        }

        return $arr;
    }

    /**
     * Get the structure of a table from database
     *
     * @param string $table_name the table name (without prefix)
     *
     * @return array sub-arrays have these keys: field, type, nullable, key, default, extra
     */
    public function getTableStructure(string $table_name)
    {
        $prefix = C::Config()->get('connections:database.prefix');
        $structure = $this->getDatabaseConnection()->select('DESCRIBE ' . $prefix . $table_name);

        $arr = [];
        foreach ($structure as $str) {
            $arr[] = [
                'field' => $str->Field,
                'type' => $str->Type,
                'nullable' => $str->Null == 'YES',
                'key' => $str->Key,
                'default' => $str->Default,
                'extra' => $str->Extra,
            ];
        }

        return $arr;
    }

    /**
     * Create a database dump
     *
     * @param string $file       absolute path to file in which the dump will be stored
     * @param string $tables     optional table name, 'all' for full database (default)
     * @param string $connection optional connection name
     *
     * @return bool true on success, false on failure
     */
    public function createDump(string $file, string $tables = 'all', string $connection = 'default'): bool
    {
        // TODO Add logic with symfony/process and native mysqldump
        // TODO Also add option to backup a single table
        return true;
    }

}