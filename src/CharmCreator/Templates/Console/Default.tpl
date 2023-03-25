---
name: Default console command
fields:
  JOB_CMD:
    name: Command
    type: input
  JOB_NAME:
    name: Display name
    type: input
  JOB_DESCRIPTION:
    name: Short description
    type: input
---
<?php
/**
 * This file contains a console command.
 */

namespace App\Jobs\Console;

use Charm\Vivid\C;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class JOB_NAME
 *
 * JOB_DESCRIPTION
 */
class JOB_NAME extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("JOB_CMD")
            ->setDescription("JOB_DESCRIPTION");
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
        $output->writeln('Hello World');

        // TODO

        return self::SUCCESS;
    }

}
