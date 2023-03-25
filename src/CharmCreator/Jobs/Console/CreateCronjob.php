<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\CharmCreator\Jobs\ConsoleHelper;
use Charm\Vivid\C;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateCronjob
 *
 * Creating cron job file
 */
class CreateCronjob extends Command
{
    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("c:cj")
            ->setDescription("Creating a cron job")
            ->setHelp('This command allows you to add a new cron job.');
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
        $type = 'cron';
        $ch = ConsoleHelper::createAndHandle($input, $output,
            $this->getHelper('question'),
            $type,
            'Creating a new cron job');

        if ($ch === false) {
            return self::FAILURE;
        }

        C::CharmCreator()->createFile($type, $ch->getAbsolutePath(), $ch->getData(), $ch->getTemplate());

        $output->writeln(' ');
        $ch->success('✅ Created cron job ' . $ch->getName());
        $output->writeln(' ');

        return self::SUCCESS;
    }
}