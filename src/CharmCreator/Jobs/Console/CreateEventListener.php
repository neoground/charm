<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Bob\Command;
use Charm\CharmCreator\Jobs\ConsoleHelper;
use Charm\Vivid\C;

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
     * @return bool
     */
    public function main(): bool
    {
        $type = 'evlistener';
        $ch = ConsoleHelper::createAndHandle($this->input, $this->output,
            $this->getHelper('question'),
            $type,
            'Creating a new event listener');

        if ($ch === false) {
            return false;
        }

        C::CharmCreator()->createFile($type, $ch->getAbsolutePath(), $ch->getData(), $ch->getTemplate());

        $this->io->writeln('');
        $this->io->success('✅ Created event listener ' . $ch->getName());
        $this->io->writeln('');

        return true;
    }
}