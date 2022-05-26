<?php
/**
 * This file contains the console command for database migration of all modules.
 */

namespace Charm\Database\Jobs\Console;

use Charm\Database\DatabaseMigrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DbSyncCommand
 *
 * Handling database migrations of all modules
 *
 * @package Charm\Bob\Jobs\Console
 */
class DbSyncCommand extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("db:sync")
            ->setDescription("Sync the database tables of all modules (drop tables with: --action=down)")
            ->setDefinition([
                new InputOption('action', 'do', InputOption::VALUE_OPTIONAL, 'Action to take: up / down', 'up'),
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
        $action = $input->getOption('action');

        $dm = new DatabaseMigrator($output);

        if ($action == 'down') {
            $dm->runAllMigrations('down', $output);
        } else {
            $dm->runAllMigrations('up', $output);
        }

        $dm->outputStats();

        $output->writeln('<info>Done!</info>');
        return Command::SUCCESS;
    }
}