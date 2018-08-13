<?php
/**
 * This file contains a console command.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Crown\Crown;
use Charm\Vivid\Charm;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->setDescription("Running the cron jobs. Should be called every minute")
            ->setDefinition([
                new InputOption('name', 'qn', InputOption::VALUE_REQUIRED,
                    'Name of queue to run', ''),
                new InputOption('worker', 'wn', InputOption::VALUE_OPTIONAL,
                    'ID of worker', 1)
            ]);
    }

    /**
     * The execution
     *
     * @param InputInterface   $input
     * @param OutputInterface  $output
     *
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getOption('name');
        $worker = $input->getOption('worker');

        if (empty(trim($name))) {
            $output->writeln('<error>No queue name set!</error>');
            exit;
        }

        if (empty(trim($worker)) || !is_numeric($worker)) {
            $worker = 1;
        }

        $output->writeln('<info>Starting queue ' . $name . ', worker ID: ' . $worker . '</info>');

        // Execute bbq
        $q = Charm::Queue();
        $q->run($name, $worker);

        $output->writeln('<info>Done!</info>');

        return true;
    }
}