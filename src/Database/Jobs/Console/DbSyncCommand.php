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
            ->setDescription("Sync the database tables of all modules")
            ->setHelp("This command is designed to synchronize the database tables across all modules of the application. It primarily focuses on ensuring that the database schema is in alignment with the current state of the application's modules.")
            ->setDefinition([
                new InputOption('action', 'do', InputOption::VALUE_OPTIONAL, 'Specifies the action to be performed on the database schema, can be either "up" or "down".', 'up'),
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
            $dm->runAllMigrations('down');
        } else {
            $dm->runAllMigrations('up');
        }

        $dm->outputStats();

        $output->writeln('<info>✅ Done!</info>');
        return Command::SUCCESS;
    }
}