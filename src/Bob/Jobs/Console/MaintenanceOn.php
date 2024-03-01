<?php
/**
 * This file contains a console command.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Bob\Command;
use Charm\Vivid\C;

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
            ->setDescription("Enables the maintenance mode")
            ->setHelp('While maintenance mode is active, no regular web requests are processed.');
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        if (C::Config()->turnMaintenanceModeOn()) {
            $this->io->success('✅ Maintenace mode turned on. App is down.');
            return true;
        }

        $this->io->writeln('<error>❌ Could not turn on maintenance mode!</error>');
        $this->io->writeln('Please create an empty var/maintenance.lock file manually and check permissions.');
        return false;
    }
}