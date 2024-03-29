<?php
/**
 * This file contains a console command.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Bob\Command;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Handler;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class SysInfo
 *
 * Info about the system and tools like systemd service generation
 *
 */
class SysInfo extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("c:sys")
            ->setDescription("Infos about the system and management tools")
            ->addArgument('type', InputArgument::OPTIONAL, 'The type of tool to use');
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $type = $this->io->getArgument('type');

        if (empty($type) || $type == 'info') {
            // Display info
            $this->io->heading1('System Info');
            $this->io->newLine();

            $info = [
                ['PHP Version', phpversion()],
                ['PHP Binary', PHP_BINARY],
                ['Charm Version', C::VERSION],
                ['Charm Environment', C::App()->getEnvironment()],
            ];

            $this->io->table(['Key', 'Value'], $info);

            $this->io->heading1('Installed Modules');
            $this->io->newLine();

            $handler = Handler::getInstance();
            $table = [];
            foreach ($handler->getModuleClasses() as $name => $module) {
                $table[] = [$name, $module];
            }

            $this->io->table(['Name', 'Class'], $table);

            return true;
        }

        return true;
    }


}