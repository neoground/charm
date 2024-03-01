<?php
/**
 * This file contains a console command.
 */

namespace Charm\Crown\Jobs\Console;

use Charm\Bob\Command;
use Charm\Crown\Crown;
use Charm\Vivid\C;
use Symfony\Component\Console\Input\InputArgument;

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
     * @return bool
     */
    public function main(): bool
    {
        /** @var Crown $c */
        $c = C::Crown();
        $c->setConsoleOutput($this->output);

        $job = $this->io->getArgument('job');
        if(!empty($job)) {
            $c->runCronjob($job);
        } else {
            $c->run();
        }

        return true;
    }
}