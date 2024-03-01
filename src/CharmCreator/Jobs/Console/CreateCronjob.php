<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Bob\Command;
use Charm\CharmCreator\Jobs\ConsoleHelper;
use Charm\Vivid\C;

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
     * @return bool
     */
    public function main(): bool
    {
        $type = 'cron';
        $ch = ConsoleHelper::createAndHandle($this->input, $this->output,
            $this->getHelper('question'),
            $type,
            'Creating a new cron job');

        if ($ch === false) {
            return false;
        }

        C::CharmCreator()->createFile($type, $ch->getAbsolutePath(), $ch->getData(), $ch->getTemplate());

        $this->io->writeln('');
        $this->io->success('✅ Created cron job ' . $ch->getName());
        $this->io->writeln('');

        return true;
    }
}