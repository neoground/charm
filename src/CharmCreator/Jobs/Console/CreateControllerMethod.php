<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Bob\Command;
use Charm\CharmCreator\Jobs\ConsoleHelper;
use Charm\Vivid\C;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class CreateControllerMethod
 *
 * Creating method in controller file
 */
class CreateControllerMethod extends Command
{
    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("c:cm")
            ->setDescription("Creating a controller method")
            ->setHelp('This command allows you to append a new method to a controller.')
            ->addOption(
                'ctrl',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Controller name'
            );
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        // Ask for template + custom fields
        $ch = ConsoleHelper::create($this->input, $this->output, $this->getHelper('question'), 'method');
        $ch->outputCharmHeader();
        $ch->outputAsciiBox('Creating a new controller method');
        $this->io->writeln('');

        // Ask for controller
        $controllerName = $this->io->getOption('ctrl');
        if (empty($controllerName)) {
            // TODO Allow selection for better UX and without needing validation
            $controllerName = $ch->ask('Name of controller class');
        }

        $ch->askForTemplateAndData();
        $ch->setDirAndNamespace();

        C::CharmCreator()->addMethodToController($ch->getAbsolutePath(), $ch->getData(), $ch->getTemplate());

        $this->io->writeln('');
        $this->io->success('✅ Added method ' . $ch->getName() . ' to controller ' . $controllerName);
        $this->io->writeln('');

        return true;
    }
}