<?php
/**
 * This file contains the init class for database.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Charm;
use Charm\Vivid\Helper\EloquentDebugbar;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\PathFinder;
use Illuminate\Database\Capsule\Manager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Database
 *
 * Database module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Database implements ModuleInterface
{
    /** @var \Illuminate\Database\Connection the eloquent database connection */
    protected $eloquent_instance;

    /**
     * Module init
     */
    public function loadModule()
    {
        // Init Eloquent
        $capsule = new Manager;

        $config = Charm::Config()->get('connections:database');

        if($config['enabled'] == true) {
            unset($config['enabled']);

            $capsule->addConnection($config);

            $capsule->setAsGlobal();

            // Setup!
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
        $debugbar = Charm::Debug()->getDebugBar();

        // Add Eloquent data if we got a database connection
        if (Charm::Config()->get('main:debug.debugmode', false)
            && Charm::Config()->get('main:debug.show_debugbar', false)) {
            // Init and add debug bar collector
            $debugbar->addCollector(new EloquentDebugbar());
        }
    }

    /**
     * Get the database connection
     *
     * @return \Illuminate\Database\Connection
     */
    public function getDatabaseConnection()
    {
        return $this->eloquent_instance;
    }

    /**
     * Get the redis client
     *
     * @deprecated use redis module instead.
     *
     * @return \Predis\Client
     */
    public function getRedisClient()
    {
        return Charm::Redis()->getClient();
    }

    /**
     * Run all database migrations
     *
     * @param string           $method  method to call (up / down)
     * @param string           $file    optional filename (part) for single migration
     * @param string           $module  optional module name where migrations should run
     * @param OutputInterface  $output  optional console output interface
     */
    public function runMigrations($method, $file = null, $module = "App", $output = null)
    {
        if ($output) {
            $output->writeln('<info>Running ' . $method . ' migrations</info>');
        }

        // Get needed data from module
        $mod = Charm::get($module);

        // Defaults
        $path = PathFinder::getAppPath() . DS . 'System' . DS . 'Migrations';
        $namespace = "\\App\\System\\Migrations";

        // Module specific
        if(is_object($mod) && method_exists($mod, 'getReflectionClass')) {
            $path = Charm::get($module)->getBaseDirectory() . DS . 'System' . DS . 'Migrations';

            $namespace = $mod->getReflectionClass()->getNamespaceName() . "\\System\\Migrations";
        }

        // Get all migration files
        if(!file_exists($path)) {
            if($output) {
                $output->writeln('<error>No migrations found for module ' . $module . '</error>');
            }
        }

        $files = glob($path . DS . '*.php');

        // Descending order for down
        if ($method == 'down') {
            $files = array_reverse($files);
        }

        // Is $file set? Single migration?
        if (!empty($file)) {
            if ($output) {
                $output->writeln('Single migration of: ' . $file);
            }

            // Remove every file which is not like the wanted name!
            foreach ($files as $k => $m) {
                if (!in_string($file, $m)) {
                    // Remove from array
                    unset($files[$k]);
                }
            }
        }

        // Go through each php file and run migration
        foreach ($files as $m) {
            require_once($m);

            // Get class name based on filename without prefix and suffix
            $class_raw = basename($m, '.php');
            $class_parts = explode("_", $class_raw);

            // Remove all numeric prefixes
            while(is_numeric($class_parts[0])) {
                array_shift($class_parts);
            }

            // Create class name with namespace
            $class = $namespace . "\\" . ucfirst(implode($class_parts, "_"));

            $migration = new $class;

            if ($output) {
                $output->writeln('Migrating: ' . $class);
            }

            if($method == 'up') {
                $migration->up();
            } else {
                $migration->down();
            }
        }

        // Finish console progress bar
        if ($output) {
            $output->writeln('<info>Finished all migrations!</info>');
        }
    }

}