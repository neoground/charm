<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Bob\Command;
use Charm\CharmCreator\Jobs\ConsoleHelper;
use Charm\Vivid\C;

/**
 * Class CreateController
 *
 * Creating controller file
 */
class CreateMigration extends Command
{
    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("c:mi")
            ->setDescription("Creating a database migration")
            ->setHelp('This command allows you to add a new database migration.');
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $type = 'migration';
        $ch = ConsoleHelper::createAndHandle($this->input, $this->output,
            $this->getHelper('question'),
            $type,
            'Creating a new database migration');

        if ($ch === false) {
            return false;
        }

        C::CharmCreator()->createFile($type, $ch->getAbsolutePath(), $ch->getData(), $ch->getTemplate());

        $this->io->writeln('');
        $this->io->success('✅ Created migration ' . $ch->getName());
        $this->io->writeln('');

        return true;
    }
}
