<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Bob\Command;
use Charm\CharmCreator\Jobs\ConsoleHelper;
use Charm\Vivid\C;

/**
 * Class CreateConsole
 *
 * Creating console command file
 */
class CreateConsole extends Command
{
    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("c:cc")
            ->setDescription("Creating a console command")
            ->setHelp('This command allows you to add a new console command.');
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $type = 'console';
        $ch = ConsoleHelper::createAndHandle($this->input, $this->output,
            $this->getHelper('question'),
            $type,
            'Creating a new console command');

        if ($ch === false) {
            return false;
        }

        C::CharmCreator()->createFile($type, $ch->getAbsolutePath(), $ch->getData(), $ch->getTemplate());

        $this->io->writeln('');
        $this->io->success('✅ Created console command ' . $ch->getName());
        $this->io->writeln('');

        return true;
    }
}