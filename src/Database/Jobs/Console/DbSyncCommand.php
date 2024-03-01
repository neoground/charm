<?php
/**
 * This file contains the console command for database migration of all modules.
 */

namespace Charm\Database\Jobs\Console;

use Charm\Bob\Command;
use Charm\Database\DatabaseMigrator;
use Symfony\Component\Console\Input\InputArgument;

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
            ->addArgument('action', InputArgument::OPTIONAL, 'Specifies the action to be performed on the database schema, can be either "up" or "down".');
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $action = $this->io->getArgument('action');

        $dm = new DatabaseMigrator($this->output);

        if ($action == 'down') {
            $dm->runAllMigrations('down');
        } else {
            $dm->runAllMigrations('up');
        }

        $dm->outputStats();

        $this->io->success('✅ Done!');
        return true;
    }
}