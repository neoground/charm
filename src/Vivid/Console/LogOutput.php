<?php
/**
 * This file contains the ProgressBar class.
 */

namespace Charm\Vivid\Console;

use Charm\Vivid\C;
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

    /**
     * Set logging channel (name of log file, see monolog for more info)
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
        if(is_iterable($messages)) {
            foreach($messages as $msg) {
                $this->log($msg);
            }
        } else {
            $this->log($messages);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, bool $newline = false, int $options = self::OUTPUT_NORMAL)
    {
        if(is_iterable($messages)) {
            foreach($messages as $msg) {
                $this->log($msg);
            }
        } else {
            $this->log($messages);
        }
    }

    /**
     * Add a messsage to the log file
     *
     * @param $msg
     *
     * @return void
     */
    public function log($msg): void
    {
        if(empty($this->channel)) {
            C::Logging()->info($msg);
        } else {
            C::Logging()
                ->withName($this->channel)
                ->info($msg);
        }
    }
}