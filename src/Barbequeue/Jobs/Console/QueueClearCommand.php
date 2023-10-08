<?php
/**
 * This file contains a console command.
 */

namespace Charm\Barbequeue\Jobs\Console;

use Charm\Vivid\C;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->setDefinition([
                new InputOption('name', 'qn', InputOption::VALUE_REQUIRED,
                    'Name of queue to run', ''),
            ]);
    }

    /**
     * The execution
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getOption('name');

        if (empty(trim($name))) {
            $output->writeln('<error>No queue name set!</error>');
            exit;
        }

        $output->writeln('<info>Clearing queue ' . $name . '</info>');

        // Execute bbq
        C::Queue()->clear($name);

        $output->writeln('<info>Done!</info>');

        return true;
    }
}