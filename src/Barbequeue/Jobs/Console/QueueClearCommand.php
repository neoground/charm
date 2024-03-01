<?php
/**
 * This file contains a console command.
 */

namespace Charm\Barbequeue\Jobs\Console;

use Charm\Bob\Command;
use Charm\Vivid\C;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class QueueClearCommand
 *
 * Handling queue clearing
 *
 * @package Charm\Bob\Commands
 */
class QueueClearCommand extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("queue:clear")
            ->setDescription("Clearing the queue")
            ->addArgument('name', InputArgument::REQUIRED, 'Name of queue to clear');
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $name = $this->io->getArgument('name');

        if (empty(trim($name))) {
            $this->io->writeln('<error>❌ No queue name set!</error>');
            exit;
        }

        $this->io->writeln('<info>Clearing queue ' . $name . '...</info>');

        // Execute bbq
        C::Queue()->clear($name);

        $this->io->writeln('✅ Done!');

        return true;
    }
}