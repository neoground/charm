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
    public function getFormatter()
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
    public function isDecorated()
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
    public function getVerbosity()
    {
        return self::VERBOSITY_QUIET;
    }

    /**
     * {@inheritdoc}
     */
    public function isQuiet()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isVerbose()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isVeryVerbose()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug()
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
                C::Logging()->info($msg);
            }
        } else {
            C::Logging()->info($messages);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, bool $newline = false, int $options = self::OUTPUT_NORMAL)
    {
        if(is_iterable($messages)) {
            foreach($messages as $msg) {
                C::Logging()->info($msg);
            }
        } else {
            C::Logging()->info($messages);
        }
    }
}