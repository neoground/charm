<?php
/**
 * This file contains the init class for logging.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\PathFinder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Class Logging
 *
 * Logging module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Logging extends Module implements ModuleInterface, LoggerInterface
{
    /** @var Logger the logger instance */
    protected $logger;

    /** @var bool Logging enabled? */
    protected $enabled = true;

    /** @var Logging[] stored custom log instances */
    protected $logger_instances = [];
    
    /**
     * Load the module
     */
    public function loadModule()
    {
        // Logging disabled?
        $this->enabled = Charm::Config()->get('main:logging.enabled', true);
        if(!$this->enabled) {
            return false;
        }

        // Build file path
        $path = PathFinder::getLogPath() . DS . date("Y-m-d") . ".log";

        // Get log level
        $loglevel_name = Charm::Config()->get('main:logging.level', 'info');
        $loglevel = Logger::toMonologLevel($loglevel_name);

        $permissions = Charm::Config()->get('main:logging.file_permission', 0664);

        $logger = new Logger("charm");
        $logger->pushHandler(new StreamHandler($path, $loglevel, true, $permissions));

        // Save instance
        $this->logger = $logger;

        // Also redirect stderr to custom log file?
        if(Charm::Config()->get('main:logging.errors', true)) {
            ini_set('error_log', PathFinder::getLogPath() . DS . date("Y-m-d") . "-errors.log");
        }

        return true;
    }

    /**
     * Add a log entry with a custom name
     *
     * This uses the channel functionality of Monolog
     *
     * @param string $name custom name
     *
     * @return Logging
     */
    public function withName($name)
    {
        if(array_key_exists($name, $this->logger_instances)) {
            return $this->logger_instances[$name];
        }

        $logging = clone $this;
        $logging->logger = $logging->logger->withName($name);

        $this->logger_instances[$name] = $logging;
        return $logging;
    }

    /**
     * Detailed debug information.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * 
     * @return bool    Whether the record has been processed
     */
    public function debug($message, array $context = [])
    {
        if(!$this->enabled) {
            return false;
        }

        if(Charm::has('Events')) {
            Charm::Event()->fire('Logging', 'debug');
        }
        if(Charm::has('DebugBar')) {
            $db = Charm::DebugBar()->getInstance();
            $db['messages']->debug($message);
        }

        return $this->logger->debug($message, $context);
    }

    /**
     * System is unusable.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     *
     * @return bool    Whether the record has been processed
     */
    public function emergency($message, array $context = [])
    {
        if(!$this->enabled) {
            return false;
        }

        if(Charm::has('Events')) {
            Charm::Event()->fire('Logging', 'emergency');
        }
        if(Charm::has('DebugBar')) {
            $db = Charm::DebugBar()->getInstance();
            $db['messages']->emergency($message);
        }

        return $this->logger->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     *
     * @return bool    Whether the record has been processed
     */
    public function alert($message, array $context = [])
    {
        if(!$this->enabled) {
            return false;
        }

        if(Charm::has('Events')) {
            Charm::Event()->fire('Logging', 'alert');
        }
        if(Charm::has('DebugBar')) {
            $db = Charm::DebugBar()->getInstance();
            $db['messages']->alert($message);
        }

        return $this->logger->alert($message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     *
     * @return bool    Whether the record has been processed
     */
    public function critical($message, array $context = [])
    {
        if(!$this->enabled) {
            return false;
        }

        if(Charm::has('Events')) {
            Charm::Event()->fire('Logging', 'critical');
        }
        if(Charm::has('DebugBar')) {
            $db = Charm::DebugBar()->getInstance();
            $db['messages']->crit($message);
        }

        return $this->logger->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     *
     * @return bool    Whether the record has been processed
     */
    public function error($message, array $context = [])
    {
        if(!$this->enabled) {
            return false;
        }

        if(Charm::has('Events')) {
            Charm::Event()->fire('Logging', 'error');
        }
        if(Charm::has('DebugBar')) {
            $db = Charm::DebugBar()->getInstance();
            $db['messages']->err($message);
        }

        return $this->logger->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     *
     * @return bool    Whether the record has been processed
     */
    public function warning($message, array $context = [])
    {
        if(!$this->enabled) {
            return false;
        }

        if(Charm::has('Events')) {
            Charm::Event()->fire('Logging', 'warning');
        }
        if(Charm::has('DebugBar')) {
            $db = Charm::DebugBar()->getInstance();
            $db['warning']->emergency($message);
        }

        return $this->logger->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     *
     * @return bool    Whether the record has been processed
     */
    public function notice($message, array $context = [])
    {
        if(!$this->enabled) {
            return false;
        }

        if(Charm::has('Events')) {
            Charm::Event()->fire('Logging', 'notice');
        }
        if(Charm::has('DebugBar')) {
            $db = Charm::DebugBar()->getInstance();
            $db['messages']->notice($message);
        }

        return $this->logger->notice($message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     *
     * @return bool    Whether the record has been processed
     */
    public function info($message, array $context = [])
    {
        if(!$this->enabled) {
            return false;
        }

        if(Charm::has('Events')) {
            Charm::Event()->fire('Logging', 'info');
        }
        if(Charm::has('DebugBar')) {
            $db = Charm::DebugBar()->getInstance();
            $db['messages']->info($message);
        }

        return $this->logger->info($message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $context The log context
     *
     * @return bool    Whether the record has been processed
     */
    public function log($level, $message, array $context = [])
    {
        if(!$this->enabled) {
            return false;
        }

        if(Charm::has('Events')) {
            Charm::Event()->fire('Logging', $level);
        }

        return $this->logger->log($level, $message, $context);
    }
}