<?php
/**
 * This file contains the ProgressBar class.
 */

namespace Charm\Bob;

use Symfony\Component\Console\Helper\ProgressBar as SProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProgressBar
 *
 * A better console progress bar
 *
 * @package Charm\Vivid\Console
 */
class ProgressBar
{
    /** @var SProgressBar the progress bar */
    protected SProgressBar $pb;

    /** @var OutputInterface the console output */
    protected OutputInterface $output;

    /**
     * Create a new progress bar
     *
     * @param OutputInterface $output An OutputInterface instance
     * @param int             $max    Maximum steps (0 if unknown)
     */
    public function __construct(OutputInterface $output, int $max = 0)
    {
        $this->pb = new SProgressBar($output, $max);
        $this->output = $output;

        // Set styling
        $this->pb->setFormat(
            "<fg=cyan>%message%</>\n%current%/%max% [%bar%] %percent:3s%%\nETA %remaining:-20s%  %memory:20s%"
        );
        $this->pb->setBarCharacter('<fg=green>=</>');
        $this->pb->setEmptyBarCharacter("<fg=red>-</>");
        $this->pb->setProgressCharacter("<fg=green>➤</>");
    }

    /**
     * Advances the progress output X steps.
     *
     * @param int $step Number of steps to advance
     */
    public function advance(int $step = 1): void
    {
        $this->pb->advance($step);
    }

    /**
     * Get the symfony progress bar instance
     *
     * @return SProgressBar
     */
    public function getProgressBarInstance(): SProgressBar
    {
        return $this->pb;
    }

    /**
     * Associates a text with a named placeholder.
     *
     * The text is displayed when the progress bar is rendered but only
     * when the corresponding placeholder is part of the custom format line
     * (by wrapping the name with %).
     *
     * @param string $message The text to associate with the placeholder
     * @param string $name    The name of the placeholder
     */
    public function setMessage(string $message, string $name = 'message'): void
    {
        $this->pb->setMessage($message, $name);
    }

    /**
     * Finishes the progress output.
     */
    public function finish(): void
    {
        $this->pb->finish();

        // Empty line for clean finish
        $this->output->writeln("");
    }
}