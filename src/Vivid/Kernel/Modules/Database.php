<?php
/**
 * This file contains the init class for database.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Exceptions\LogicException;
use Charm\Vivid\Helper\EloquentDebugbar;
use Charm\Vivid\Kernel\Handler;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Illuminate\Database\Capsule\Manager;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Databases\PostgreSql;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Database
 *
 * Database module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Database extends Module implements ModuleInterface
{
    /** @var \Illuminate\Database\Connection the eloquent database connection */
    protected $eloquent_instance;

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

        if(is_array($config) && $config['enabled'] == true) {
            unset($config['enabled']);

            $capsule->addConnection($config);
            $db_available = true;
        }

        // Add multiple databases
        $config = C::Config()->get('connections:databases');

        if(is_array($config)) {
            foreach($config as $name => $dbvals) {
                if(is_array($dbvals) && $dbvals['enabled'] == true) {
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
        return C::Redis()->getClient();
    }

    /**
     * Run all database migrations of a module
     *
     * @param string          $method method to call (up / down)
     * @param string          $file   optional filename (part) for single migration
     * @param string          $module optional module name which should be migrated
     * @param OutputInterface $output optional console output interface
     */
    public function runMigrations(string $method, $file = null, $module = "App", $output = null)
    {
        // Get needed data from module
        $mod = C::get($module);

        // Defaults
        $path = C::Storage()->getAppPath() . DS . 'System' . DS . 'Migrations';
        $namespace = "\\App\\System\\Migrations";

        // Module specific
        if(is_object($mod) && method_exists($mod, 'getReflectionClass')) {
            $path = C::get($module)->getBaseDirectory() . DS . 'System' . DS . 'Migrations';

            $namespace = $mod->getReflectionClass()->getNamespaceName() . "\\System\\Migrations";
        }

        // Get all migration files
        if(!file_exists($path)) {
            if($output) {
                $output->writeln('No migrations found for module: ' . $module);
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
            $class = $namespace . "\\" . implode("",  array_map("ucfirst", $class_parts));

            if(!class_exists($class)) {

                // Append table suffix. Some people like that.
                $class = $class . 'Table';

                if(!class_exists($class)) {
                    // Still not found. Ignore.
                    if($output) {
                        $output->writeln('<error>Invalid class in: ' . $class_raw
                            . '. Expected: ' . $class . '</error>');
                    }
                    continue;
                }
            }

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

        // Run migrations in models
        if ($output) {
            $output->writeln('<info>Running ' . $method . ' migrations in models</info>');
        }
        $this->runModelMigrations($method, $module, $output);
    }

    /**
     * Run all database migrations of all modules
     *
     * @param string           $method  method to call (up / down)
     * @param OutputInterface  $output  optional console output interface
     */
    public function runAllMigrations($method, $output = null)
    {
        foreach(Handler::getInstance()->getModuleClasses() as $name => $module) {
            if ($output) {
                $output->writeln('<info>Running ' . $method . ' migrations for module: ' . $name . '</info>');
            }

            $this->runMigrations($method, null, $name, $output);
        }

        $this->runMigrations($method, null, "App", $output);
    }

    /**
     * Run migrations of all model files of a module
     *
     * @param string $method migration method (up / down)
     * @param string $module wanted module
     * @param null|OutputInterface $output optional console output
     */
    private function runModelMigrations($method, $module = "App", $output = null)
    {
        try {
            $mod = C::get($module);

            if(is_object($mod)) {
                $models_dir = $mod->getBaseDirectory() . DS . 'Models';
                $namespace = $mod->getReflectionClass()->getNamespaceName() . "\\Models";

                $schema_builder = $this->getDatabaseConnection()->getSchemaBuilder();

                if(file_exists($models_dir)) {

                    foreach(C::Storage()->scanDirForFiles($models_dir) as $file) {
                        // Check if getTableStructure() method is existing in model class

                        $fullpath = $models_dir . DS . $file;
                        $pathinfo = pathinfo($fullpath);
                        require_once($fullpath);

                        $class = $namespace . "\\" . $pathinfo['filename'];

                        if(method_exists($class, "getTableStructure")) {
                            $obj = new $class;
                            $tablename = $obj->getTable();

                            if($method == 'down') {

                                // DOWN migration

                                if($output) {
                                    $output->writeln('Dropping table: ' . $tablename);
                                }

                                $schema_builder->dropIfExists($tablename);

                            } else {

                                // UP migration
                                if (!$schema_builder->hasTable($tablename)) {

                                    if($output) {
                                        $output->writeln('Creating table: ' . $tablename);
                                    }

                                    $schema_builder->create($tablename, $obj::getTableStructure());
                                } else {

                                    if($output) {
                                        $output->writeln('Ignoring existing table: ' . $tablename);
                                    }

                                }

                            }


                        }

                    }

                }
            }
        } catch(\Exception $e) {
            // Invalid module or file -> ignore.
        }

    }

    /**
     * Create a database dump
     *
     * @param string $name optional connection name
     *
     * @return MySql|PostgreSql
     *
     * @throws LogicException
     */
    public function createDump($name = 'default')
    {
        // Get database config
        $db_type = false;
        $db_username = false;
        $db_password = false;
        $db_name = false;

        // Single database (default)
        $config = C::Config()->get('connections:database');

        if(is_array($config) && $name == 'default') {
            // Got wanted entry
            $db_type = $config['driver'];
            $db_username = $config['username'];
            $db_password = $config['password'];
            $db_name = $config['database'];
        }

        // Multiple databases
        $config = C::Config()->get('connections:databases');

        if(is_array($config)) {
            foreach($config as $confname => $dbvals) {
                if(is_array($dbvals) && $confname == $name) {
                    // Got wanted entry
                    $db_type = $dbvals['driver'];
                    $db_username = $dbvals['username'];
                    $db_password = $dbvals['password'];
                    $db_name = $dbvals['database'];
                }
            }
        }

        switch($db_type) {
            case 'mysql':
                return MySql::create()
                    ->setDbName($db_name)
                    ->setUserName($db_username)
                    ->setPassword($db_password);
                break;
            case 'postgresql':
                return PostgreSql::create()
                    ->setDbName($db_name)
                    ->setUserName($db_username)
                    ->setPassword($db_password);
                break;
        }

        throw new LogicException("Valid database connection not found");
    }

}