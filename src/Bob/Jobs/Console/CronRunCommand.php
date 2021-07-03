<?php
/**
 * This file contains a console command.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Crown\Crown;
use Charm\Vivid\C;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CronRunCommand
 *
 * Handling cron job running
 *
 * @package Charm\Bob\Jobs\Console
 */
class CronRunCommand extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("cron:run")
            ->setDescription("Running the cron jobs. Should be called every minute");
    }

    /**
     * The execution
     *
     * @param InputInterface   $input
     * @param OutputInterface  $output
     *
     * @return int
     *
     * @throws \Charm\Crown\Exceptions\InvalidCronjobException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Crown $c */
        $c = C::Crown();
        $c->setConsoleOutput($output);
        $c->run();
        return Command::SUCCESS;
    }
}