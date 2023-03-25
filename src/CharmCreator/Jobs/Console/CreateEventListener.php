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
 * Class CreateEventListener
 *
 * Creating event listener file
 */
class CreateEventListener extends Command
{
    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("c:ev")
            ->setDescription("Creating an event listener")
            ->setHelp('This command allows you to add a new event listener.');
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
        $type = 'evlistener';
        $ch = ConsoleHelper::createAndHandle($input, $output,
            $this->getHelper('question'),
            $type,
            'Creating a new event listener');

        if ($ch === false) {
            return self::FAILURE;
        }

        C::CharmCreator()->createFile($type, $ch->getAbsolutePath(), $ch->getData(), $ch->getTemplate());

        $output->writeln(' ');
        $ch->success('✅ Created event listener ' . $ch->getName());
        $output->writeln(' ');

        return self::SUCCESS;
    }
}