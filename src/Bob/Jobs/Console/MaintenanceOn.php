<?php
/**
 * This file contains a console command.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Bob\Command;
use Charm\Vivid\C;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MaintenanceOn
 *
 * This class represents a console command to turn on the maintenance mode.
 *
 * @package Charm\Bob\Jobs\Console
 */
class MaintenanceOn extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("cc:down")
            ->setDescription("Enables the maintenance mode. While maintenance mode is active, no regular web requests are processed.");
    }

    /**
     * The execution
     *
     * @param InputInterface   $input
     * @param OutputInterface  $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(C::Config()->turnMaintenanceModeOn()) {
            $output->writeln('<info>✅ Maintenace mode turned on. App is down.</info>');
            return Command::SUCCESS;
        }

        $output->writeln('<error>❌ Could not turn on maintenance mode!</error>');
        $output->writeln('Please create an empty var/maintenance.lock file manually and check permissions.');
        return Command::FAILURE;
    }
}