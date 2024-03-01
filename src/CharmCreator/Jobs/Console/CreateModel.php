<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Bob\Command;
use Charm\CharmCreator\Jobs\ConsoleHelper;
use Charm\Vivid\C;

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
     * @return bool
     */
    public function main(): bool
    {
        $type = 'model';
        $ch = ConsoleHelper::createAndHandle($this->input, $this->output, $this->getHelper('question'), $type,
            'Creating a new model');

        if ($ch === false) {
            return false;
        }

        C::CharmCreator()->createFile($type, $ch->getAbsolutePath(), $ch->getData(), $ch->getTemplate());

        $this->io->writeln('');
        $this->io->success('✅ Created model ' . $ch->getName());
        $this->io->writeln('');

        return true;
    }
}