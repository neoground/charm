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
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class CreateController
 *
 * Creating controller file
 */
class CreateController extends Command
{
    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("c:c")
            ->setDescription("Creating a controller")
            ->setHelp('This command allows you to add a new controller.');
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
        $ch = ConsoleHelper::createAndHandle($input, $output, $this->getHelper('question'), 'controller');

        if($ch === false) {
            return self::FAILURE;
        }

        $ch->outputCharmHeader();
        $ch->outputAsciiBox('Creating a new controller');
        $output->writeln(' ');

        C::CharmCreator()->createController($ch->getAbsolutePath(), $ch->getData(), $ch->getTemplate());

        $output->writeln('✅ Created controller ' . $ch->getName());

        return self::SUCCESS;
    }
}