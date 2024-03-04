<?php
/**
 * This file contains a console command.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Bob\Command;
use Charm\Vivid\C;

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
        $this->setName("c:up")
            ->setDescription("Deactivates the maintenance mode")
            ->setHelp('This allows the application to accept requests again.');
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        if (C::Config()->turnMaintenanceModeOn()) {
            $this->io->success('✅ Maintenace mode turned off. App is up again.');
            return true;
        }

        $this->io->writeln('<error>❌ Could not turn off maintenance mode!</error>');
        $this->io->writeln('Please remove the var/maintenance.lock file manually and check permissions.');
        return false;
    }
}