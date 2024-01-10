<?php
/**
 * This file contains the console command for database migration.
 */

namespace Charm\Database\Jobs\Console;

use Charm\Database\DatabaseMigrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DbMigrateCommand
 *
 * Handling database migrations
 *
 * @package Charm\Bob\Jobs\Console
 */
class DbMigrateCommand extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("db:migrate")
            ->setDescription("Migrate the database tables")
            ->setHelp('This command facilitates the migration of database tables. It allows the user to specify whether to upgrade or downgrade the database schema, target specific migration files, or apply migrations to specific modules within the application.')
            ->setDefinition([
                new InputOption('action', 'do', InputOption::VALUE_REQUIRED, 'Specifies the action to be performed on the database schema, can be either "up" or "down".', 'up'),
                new InputOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Optional. If provided, specifies a single migration file to be migrated. Only provide the filename.', ''),
                new InputOption('module', 'm', InputOption::VALUE_OPTIONAL, 'Optional. Specifies the module where the migration should be applied.', 'App')
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
        $file = $input->getOption('file');
        $module = $input->getOption('module');

        $dm = new DatabaseMigrator($output);

        if ($action == 'up') {
            $dm->runMigrations('up', $file, $module);
        } elseif ($action == 'down') {
            $dm->runMigrations('down', $file, $module);
        }

        $output->writeln('<info>✅ Done!</info>');
        return Command::SUCCESS;
    }
}