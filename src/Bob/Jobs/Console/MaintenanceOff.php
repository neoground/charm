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
 * Class MaintenanceOff
 *
 *  This class represents a console command to turn off the maintenance mode.
 *
 * @package Charm\Bob\Jobs\Console
 */
class MaintenanceOff extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("cc:up")
            ->setDescription("Deactivates the maintenance mode allowing application to accept requests again.");
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
            $output->writeln('<info>✅ Maintenace mode turned off. App is up again.</info>');
            return Command::SUCCESS;
        }

        $output->writeln('<error>❌ Could not turn off maintenance mode!</error>');
        $output->writeln('Please remove the var/maintenance.lock file manually and check permissions.');
        return Command::FAILURE;
    }
}