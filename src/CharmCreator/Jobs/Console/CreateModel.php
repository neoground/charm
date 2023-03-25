<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\CharmCreator\Jobs\ConsoleHelper;
use Charm\Vivid\C;
use Charm\Bob\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class CreateModelCommand
 *
 * Creating model files
 *
 * @package Charm\CharmCreator\Jobs\Console
 */
class CreateModel extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("c:m")
            ->setDescription("Creating a model")
            ->setHelp('This command allows you to add a new model.');
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
        $ch = ConsoleHelper::createAndHandle($input, $output, $this->getHelper('question'), 'model');

        if($ch === false) {
            return self::FAILURE;
        }

        $ch->outputCharmHeader();
        $ch->outputAsciiBox('Creating a new model');
        $output->writeln(' ');

        C::CharmCreator()->createModel($ch->getAbsolutePath(), $ch->getData(), $ch->getTemplate());

        $output->writeln('✅ Created model ' . $ch->getName());

        return self::SUCCESS;
    }
}