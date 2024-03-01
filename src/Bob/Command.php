<?php
/**
 * This file contains the Bob class
 */

namespace Charm\Bob;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Command
 *
 * This class includes Charm-specific features to
 * enhance the functionality of console commands.
 * It allows developers to create and manage custom
 * console commands that perform various tasks,
 * such as generating files, processing data,
 * and executing other custom functionality.
 *
 * Based on symfony/console.
 */
class Command extends \Symfony\Component\Console\Command\Command
{
    protected OutputInterface $output;
    protected InputInterface $input;
    protected CommandHelper $io;

    /**
     * Sets the output.
     *
     * @param OutputInterface $output The output interface to set.
     *
     * @return self
     */
    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;
        return $this;
    }

    /**
     * Sets the input for the object.
     *
     * @param InputInterface $input The input to be set.
     *
     * @return self Returns the object itself for method chaining.
     */
    public function setInput(InputInterface $input): self
    {
        $this->input = $input;
        return $this;
    }

    /**
     * Initializes the CommandHelper object.
     *
     * Make sure input and output are set before calling this method.
     *
     * @return self
     */
    public function initIO(): self
    {
        $this->io = new CommandHelper($this->input, $this->output);
        return $this;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->input = $input;
        $this->initIO();
        return $this->main() ? self::SUCCESS : self::FAILURE;
    }

    public function main(): bool
    {
        return false;
    }

}