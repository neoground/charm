---
name: Default console command
fields:
  JOB_CMD:
    name: Command
    type: input
  JOB_NAME:
    name: Display name
    type: input
  JOB_DESCRIPTION:
    name: Short description
    type: input
---
<?php
/**
 * This file contains a console command.
 */

namespace App\Jobs\Console;

use Charm\Bob\Command;
use Charm\Vivid\C;

/**
 * Class JOB_NAME
 *
 * JOB_DESCRIPTION
 */
class JOB_NAME extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("JOB_CMD")
            ->setDescription("JOB_DESCRIPTION");
    }

    /**
     * The execution
     */
    public function main(): bool
    {
        $this->io->text('Hello world!');

        // TODO

        return true;
    }

}
