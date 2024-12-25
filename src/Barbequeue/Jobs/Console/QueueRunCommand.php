<?php
/**
 * This file contains a console command.
 */

namespace Charm\Barbequeue\Jobs\Console;

use Charm\Bob\Command;
use Charm\Vivid\C;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class QueueRunCommand
 *
 * Handling queue running
 *
 * @package Charm\Bob\Commands
 */
class QueueRunCommand extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("queue:run")
            ->setDescription("Running the queue. Should be called every 5 or 10 minutes")
            ->addOption('name', 'qn', InputOption::VALUE_REQUIRED, 'Name of queue to run', '')
            ->addOption('worker', 'wn', InputOption::VALUE_OPTIONAL, 'ID of worker', 1);
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $name = $this->io->getOption('name');
        $worker = $this->io->getOption('worker');

        if (empty(trim($name))) {
            $this->io->writeln('<error>❌ No queue name set!</error>');
            exit;
        }

        if (empty(trim($worker)) || !is_numeric($worker)) {
            $worker = 1;
        }

        $this->io->writelnVerbose('<info>Starting queue ' . $name . ', worker ID: ' . $worker . '</info>');

        // Execute bbq
        C::Queue()->run($name, $worker);

        $this->io->writelnVerbose('✅ Done!');

        return true;
    }
}