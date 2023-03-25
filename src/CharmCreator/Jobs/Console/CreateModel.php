<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Bob\Command;
use Charm\CharmCreator\Jobs\ConsoleHelper;
use Charm\Vivid\C;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $type = 'model';
        $ch = ConsoleHelper::createAndHandle($input, $output, $this->getHelper('question'), $type,
            'Creating a new model');

        if ($ch === false) {
            return self::FAILURE;
        }

        C::CharmCreator()->createFile($type, $ch->getAbsolutePath(), $ch->getData(), $ch->getTemplate());

        $output->writeln(' ');
        $ch->success('✅ Created model ' . $ch->getName());
        $output->writeln(' ');

        return self::SUCCESS;
    }
}