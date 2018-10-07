<?php
/**
 * This file contains a console command.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Vivid\Charm;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param InputInterface   $input
     * @param OutputInterface  $output
     *
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Removing AppStorage cache file...</info>');
        Charm::AppStorage()->clearCache();

        // Also clear opcache
        if(function_exists('opcache_reset')) {
            opcache_reset();
        }

        $output->writeln('Done!');
        return true;
    }
}