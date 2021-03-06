<?php
/**
 * This file contains a console command.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Vivid\C;
use Symfony\Component\Console\Command\Command;
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
     * @param InputInterface   $input
     * @param OutputInterface  $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Generating AppStorage cache file...</info>');
        C::AppStorage()->generateCache();

        // Also clear opcache
        if(function_exists('opcache_reset')) {
            opcache_reset();
        }

        $output->writeln('Done!');
        return Command::SUCCESS;
    }
}