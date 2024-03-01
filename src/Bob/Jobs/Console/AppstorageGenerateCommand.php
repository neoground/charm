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
 * Class AppstorageGenerateCommand
 *
 * Handling app storage cache generation
 *
 * @package Charm\Bob\Jobs\Console
 */
class AppstorageGenerateCommand extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("appstorage:generate")
            ->setDescription("Generate and replace the AppStorage cache");
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $this->io->writeln('<info>Generating AppStorage cache file...</info>');
        C::AppStorage()->generateCache();

        // Also clear opcache
        if(function_exists('opcache_reset')) {
            opcache_reset();
        }

        $this->io->success('✅ Done!');
        return true;
    }
}