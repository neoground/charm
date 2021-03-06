<?php
/**
 * This file contains the console command for database migration.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Vivid\C;
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
            ->setDescription("Migrate the database tables (parameters: --action=up / --action=down)")
            ->setDefinition([
                new InputOption('action', 'do', InputOption::VALUE_REQUIRED, 'Action to take: up / down', 'up'),
                new InputOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Optional single file to migrate', ''),
                new InputOption('module', 'm', InputOption::VALUE_OPTIONAL, 'Optional module name', 'App')
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

        if (!empty($file)) {
            // Single migration
            $output->writeln('<info>Starting single migration</info>');
        } else {
            // File empty or not set -> full migration!
            $output->writeln('<info>Starting migrations</info>');
            $file = null;
        }

        if ($action == 'up') {
            $output->writeln('<info>Running UP migrations</info>');
            C::Database()->runMigrations('up', $file, $module, $output);
        } elseif ($action == 'down') {
            $output->writeln('<info>Running DOWN migrations</info>');
            C::Database()->runMigrations('down', $file, $module, $output);
        }

        $output->writeln('<info>Done!</info>');
        return Command::SUCCESS;
    }
}