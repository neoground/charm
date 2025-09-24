<?php
/**
 * This file contains the init class for logging.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\ProcessHandler;
use Monolog\Handler\RedisHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
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
    /** @var bool Logging enabled? */
    protected bool $enabled = true;

    /** @var Logger[] stored log instances */
    protected array $logger_instances = [];

    protected string $active_logger = 'charm';

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

        // Init default logger (charm)
        $this->initLogger('charm', ['type' => 'file']);
        $this->active_logger = C::Config()->get('main:logging.default_logger', 'charm');

        // Also redirect stderr to custom log file?
        if (C::Config()->get('main:logging.errors', true)) {
            ini_set('error_log', C::Storage()->getLogPath() . DS . date("Y-m-d") . "-errors.log");
        }

        return true;
    }

    private function initLogger(string $key, array $logger_config = []): Logger|false
    {
        if (empty($logger_config)) {
            $logger_config = C::Config()->get('main:logging.loggers.' . $key, []);
        }

        if (empty($logger_config)) {
            return false;
        }

        $loglevel = Logger::toMonologLevel($logger_config['level'] ?? C::Config()->get('main:logging.level', 'info'));
        $logger = new Logger($key);

        switch (strtolower($logger_config['type'])) {
            case 'file':
                // Build file path
                $suffix = ($key == 'charm') ? '.log' : '-' . $key . '.log';
                $path = C::Storage()->getLogPath() . DS . date("Y-m-d") . $suffix;
                $permissions = C::Config()->get('main:logging.file_permission', 0664);
                $logger->pushHandler(new StreamHandler($path, $loglevel, true, $permissions));
                break;
            case 'redis':
                $logger->pushHandler(new RedisHandler(C::Redis()->getClient(), $logger_config['key'] ?? 'charm:log', $loglevel, true));
                break;
            case 'syslog':
                $logger->pushHandler(new SyslogHandler($logger_config['ident'] ?? 'charm', level: $loglevel));
                break;
            case 'process':
                $logger->pushHandler(new ProcessHandler($logger_config['command'], level: $loglevel));
                break;
            case 'errorlog':
                $logger->pushHandler(new ErrorLogHandler(level: $loglevel));
                break;
            // TODO Add more drivers for common use cases
            //      See https://github.com/Seldaek/monolog/blob/main/doc/02-handlers-formatters-processors.md
        }

        $this->logger_instances[$key] = $logger;
        return $logger;
    }

    /**
     * Add a log entry via a custom logger
     *
     * @param string $key logger key
     *
     * @return Logging
     */
    public function via(string $key): self
    {
        if (!array_key_exists($key, $this->logger_instances)) {
            if (!$this->initLogger($key)) {
                return $this;
            }
        }

        $x = clone $this;
        $x->active_logger = $key;
        return $x;
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
            C::Event()->fire('Logging', $level, [
                'message' => $message,
                'context' => $context,
            ]);
        }

        if (C::has('DebugBar') && C::DebugBar()->isEnabled()) {
            $db = C::DebugBar()->getInstance();
            $db['messages']->$level($message);
        }

        $this->logger_instances[$this->active_logger]->addRecord(Logger::toMonologLevel($level), $message, $context);
    }
}