<?php
/**
 * This file contains the console command for database migration.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Vivid\Charm;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DbMigrateCommand
 *
 * Handling database migrations
 *
 * @package Charm\Bob\Commands
 */
class DbMigrateCommand extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("db:migrate")
            ->setDescription("Migrate the database tables (parameters: --action=up / --action=down)")
            ->setDefinition([
                new InputOption('action', 'do', InputOption::VALUE_REQUIRED, 'Action to take: up / down', 'up'),
                new InputOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Optional single file to migrate', '')
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

        if (!empty($file)) {
            // Single migration
            $output->writeln('<info>Starting single migration</info>');
        } else {
            // File empty or not set -> full migration!
            $output->writeln('<info>Starting migrations</info>');
            $file = null;
        }

        if ($action == 'up') {
            Charm::Database()->runMigrations('up', $file, $output);
        } elseif ($action == 'down') {
            Charm::Database()->runMigrations('down', $file, $output);
        }

        $output->writeln('<info>Done!</info>');
        return true;
    }
}