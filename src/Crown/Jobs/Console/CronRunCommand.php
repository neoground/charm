<?php
/**
 * This file contains a console command.
 */

namespace Charm\Crown\Jobs\Console;

use Charm\Crown\Crown;
use Charm\Vivid\C;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CronRunCommand
 *
 * Handling cron job running
 */
class CronRunCommand extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("cron:run")
            ->setDescription("Running the cron jobs. Should be called every minute")
            ->addArgument('job', InputArgument::OPTIONAL, 'Optional job class to run single cron job right now');
    }

    /**
     * The execution
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Crown $c */
        $c = C::Crown();
        $c->setConsoleOutput($output);

        $job = $input->getArgument('job');
        if(!empty($job)) {
            $c->runCronjob($job);
        } else {
            $c->run();
        }

        return self::SUCCESS;
    }
}