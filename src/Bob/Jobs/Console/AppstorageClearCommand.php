<?php
/**
 * This file contains a console command.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Bob\Command;
use Charm\Vivid\C;

/**
 * Class AppstorageClearCommand
 *
 * Handling app storage cache clearing
 *
 * @package Charm\Bob\Jobs\Console
 */
class AppstorageClearCommand extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("appstorage:clear")
            ->setDescription("Clear the AppStorage cache");
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $this->io->writeln('<info>Removing AppStorage cache file...</info>');
        C::AppStorage()->clearCache();

        // Also clear opcache
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        $this->io->success('✅ Done!');
        return true;
    }
}