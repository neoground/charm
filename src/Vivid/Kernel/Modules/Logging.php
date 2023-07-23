<?php
/**
 * This file contains the init class for logging.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
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
        $this->enabled = C::Config()->get('main:logging.enabled', true);
        if (!$this->enabled) {
            return false;
        }

        // Build file path
        $path = C::Storage()->getLogPath() . DS . date("Y-m-d") . ".log";

        // Get log level
        $loglevel_name = C::Config()->get('main:logging.level', 'info');
        $loglevel = Logger::toMonologLevel($loglevel_name);

        $permissions = C::Config()->get('main:logging.file_permission', 0664);

        $logger = new Logger("charm");
        $logger->pushHandler(new StreamHandler($path, $loglevel, true, $permissions));

        // Save instance
        $this->logger = $logger;

        // Also redirect stderr to custom log file?
        if (C::Config()->get('main:logging.errors', true)) {
            ini_set('error_log', C::Storage()->getLogPath() . DS . date("Y-m-d") . "-errors.log");
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
        if (array_key_exists($name, $this->logger_instances)) {
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
     * @param \Stringable|string $message The log message
     * @param array              $context The log context
     */
    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * System is unusable.
     *
     * @param string $message The log message
     * @param array  $context The log context
     */
    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param \Stringable|string $message The log message
     * @param array              $context The log context
     */
    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param \Stringable|string $message The log message
     * @param array              $context The log context
     */
    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param \Stringable|string $message The log message
     * @param array              $context The log context
     */
    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param \Stringable|string $message The log message
     * @param array              $context The log context
     */
    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param \Stringable|string $message The log message
     * @param array              $context The log context
     */
    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param \Stringable|string $message The log message
     * @param array              $context The log context
     */
    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed              $level   The log level
     * @param \Stringable|string $message The log message
     * @param array              $context The log context
     */
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $level = strtolower($level);

        if (C::has('Event')) {
            C::Event()->fire('Logging', $level);
        }

        if (C::has('DebugBar') && C::DebugBar()->isEnabled()) {
            $db = C::DebugBar()->getInstance();
            $db['messages']->$level($message);
        }

        $this->logger->addRecord(Logger::toMonologLevel($level), $message, $context);
    }
}