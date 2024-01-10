<?php
/**
 * This file contains the LogOutput class.
 */

namespace Charm\Vivid\Console;

use Charm\Vivid\C;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Formatter\NullOutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LogOutput
 *
 * Saving console command output to logs
 *
 * @package Charm\Vivid\Console
 */
class LogOutput implements OutputInterface
{
    private $formatter;

    protected string $channel;

    protected Logger $logger;

    /**
     * Set logging channel (name of log file)
     *
     * @param string $name
     *
     * @return $this
     */
    public function toChannel(string $name) : self
    {
        $this->channel = $name;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormatter(OutputFormatterInterface $formatter)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatter() : OutputFormatterInterface
    {
        if ($this->formatter) {
            return $this->formatter;
        }
        // to comply with the interface we must return a OutputFormatterInterface
        return $this->formatter = new NullOutputFormatter();
    }

    /**
     * {@inheritdoc}
     */
    public function setDecorated(bool $decorated)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function isDecorated(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setVerbosity(int $level)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function getVerbosity(): int
    {
        return self::VERBOSITY_QUIET;
    }

    /**
     * {@inheritdoc}
     */
    public function isQuiet() : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isVerbose() : bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isVeryVerbose() : bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug() : bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function writeln($messages, int $options = self::OUTPUT_NORMAL)
    {
        $level = 'info';
        if($options == self::VERBOSITY_VERBOSE
            || $options == self::VERBOSITY_VERY_VERBOSE
            || $options == self::VERBOSITY_DEBUG) {
            // Debug message
            $level = 'debug';
        }

        if(is_iterable($messages)) {
            foreach($messages as $msg) {
                $this->log($msg, $level);
            }
        } else {
            $this->log($messages, $level);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, bool $newline = false, int $options = self::OUTPUT_NORMAL)
    {
        $this->writeln($messages, $options);
    }

    /**
     * Add a messsage to the log file
     *
     * @param $msg
     *
     * @return bool true on success, false if empty or invalid
     */
    public function log($msg, $level = 'info'): bool
    {
        // Replace square brackets for better parsing
        $msg = str_replace("[", "(", $msg);
        $msg = str_replace("]", ")", $msg);

        if($level == 'info') {
            if(str_contains($msg, '<error>')) {
                $level = 'error';
            }
        }

        // Also strip tags (no formatting)
        $msg = strip_tags($msg);

        $msg = trim($msg);

        if(empty($msg)) {
            return false;
        }

        if(empty($this->channel)) {
            C::Logging()->$level($msg);
        } else {
            $this->getLogger()->$level($msg);
        }
        return true;
    }

    /**
     * Get monolog logger instance
     *
     * @return Logger
     */
    public function getLogger(): Logger
    {
        if(empty($this->logger)) {
            $path = C::Storage()->getLogPath() . DS . date("Y-m-d") . '-' . $this->channel . '.log';
            $logger = new Logger('Task');
            $loglevel = Logger::toMonologLevel(C::Config()->get('main:logging.logoutput.level', 'info'));
            $permissions = C::Config()->get('main:logging.file_permission', 0664);
            $logger->pushHandler(new StreamHandler($path, $loglevel, true, $permissions));

            $this->logger = $logger;
        }

        return $this->logger;
    }

    /**
     * Magic methods from logger
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed|false
     */
    public function __call($name, $arguments)
    {
        if(is_object($this->logger) && method_exists($this->logger, $name)) {
            return call_user_func_array([$this->logger, $name], $arguments);
        }

        return false;
    }


}