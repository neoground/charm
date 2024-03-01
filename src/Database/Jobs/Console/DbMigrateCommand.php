<?php
/**
 * This file contains the console command for database migration.
 */

namespace Charm\Database\Jobs\Console;

use Charm\Bob\Command;
use Charm\Database\DatabaseMigrator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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
            ->addArgument('action', InputArgument::OPTIONAL, 'Specifies the action to be performed on the database schema, can be either "up" or "down".')
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Optional. If provided, specifies a single migration file to be migrated. Only provide the filename.', '')
            ->addOption('module', 'm', InputOption::VALUE_OPTIONAL, 'Optional. Specifies the module where the migration should be applied.', 'App');
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $action = $this->io->getArgument('action');
        $file = $this->io->getOption('file');
        $module = $this->io->getOption('module');

        $dm = new DatabaseMigrator($this->output);

        if ($action == 'down') {
            $dm->runMigrations('down', $file, $module);

        } else {
            $dm->runMigrations('up', $file, $module);
        }

        $this->io->success('✅ Done!');
        return true;
    }
}