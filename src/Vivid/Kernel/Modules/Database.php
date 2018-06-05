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
use Predis\Client;
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

    /** @var Client|\Redis redis client */
    protected $redis_client;

    /**
     * Module init
     */
    public function loadModule()
    {
        // Init Eloquent
        $this->loadEloquent();

        // Init Redis
        $this->loadRedis();
    }

    /**
     * Init Eloquent
     */
    private function loadEloquent()
    {
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
     * Init Redis
     *
     * @return bool
     */
    private function loadRedis()
    {
        if (Charm::Config()->get('connections:redis.enabled', true)) {
            $host = Charm::Config()->get('connections:redis.host', '127.0.0.1');
            $port = Charm::Config()->get('connections:redis.port', 6379);
            $pw = Charm::Config()->get('connections:redis.password');
            $persistent = Charm::Config()->get('connections:redis.persistent', false);

            if(class_exists("\\Redis")) {
                // Use native redis driver
                $redis = new \Redis();

                if($persistent) {
                    $redis->pconnect($host, $port);
                } else {
                    $redis->connect($host, $port);
                }

                if(!empty($pw)) {
                    $redis->auth($pw);
                }

                // Auto serialize
                $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

                // Set prefix
                $redis->setOption(\Redis::OPT_PREFIX, Charm::Config()->get('connections:redis.prefix'));

                $this->redis_client = $redis;
                return true;
            }

            // Use Predis
            $options = [];

            // Set prefix
            $options['prefix'] = Charm::Config()->get('connections:redis.prefix');

            // Set redis password
            if (!empty($pw)) {
                $options['parameters'] = [
                    'password' => $pw
                ];
            }

            // Create client
            $this->redis_client = new Client([
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
                'persistent' => $persistent
            ], $options);

            return true;
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
     * @return Client
     */
    public function getRedisClient()
    {
        return $this->redis_client;
    }

    /**
     * Run all database migrations
     *
     * @param string           $method  method to call (up / down)
     * @param string           $file    optional filename (part) for single migration
     * @param OutputInterface  $output  optional console output interface
     */
    public function runMigrations($method, $file = null, $output = null)
    {
        if ($output) {
            $output->writeln('<info>Running ' . $method . ' migrations</info>');
        }

        // Get all migration files

        $path = PathFinder::getAppPath() . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'Migrations';

        $files = glob($path . DIRECTORY_SEPARATOR . '*.php');

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
            $class = "\\App\\System\\Migrations\\" .  ucfirst(implode($class_parts, "_"));

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