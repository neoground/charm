<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Bob\Command;
use Charm\CharmCreator\Jobs\ConsoleHelper;
use Charm\Vivid\C;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class CreateConsole
 *
 * Creating a new file based on a template and variables
 */
class CreateFile extends Command
{
    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("c:new")
            ->setDescription("Create a new file (e.g. controller, model)")
            ->setHelp('This command allows you to add a new file based on a template and variables.' . "\n"
                . "You can create those using one of the available type string:\n"
                . "Controller:         ctrl, controller\n"
                . "Controller Method:  cm, method\n"
                . "Model:              mod, model\n"
                . "Console Command:    cli, console\n"
                . "Cron Job:           cron, cronjob\n"
                . "Event Listener:     el, event, listener, eventlistener\n"
                . "Database Migration: db, table, migration\n"
            )
            ->addArgument('type', InputArgument::REQUIRED, 'The type of file to create, see help for all possible values.');
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $inputtype = $this->io->getArgument('type');

        switch(strtolower($inputtype)) {
            case 'ctrl':
            case 'controller':
                $type = 'controller';
                $header = 'Creating a new controller';
                $return_text = 'Created controller';
                break;
            case 'cli':
            case 'console':
                $type = 'console';
                $header = 'Creating a new console command';
                $return_text = 'Created console command';
                break;
            case 'cron':
            case 'cronjob':
                $type = 'cron';
                $header = 'Creating a new cron job';
                $return_text = 'Created cron job';
                break;
            case 'el':
            case 'event':
            case 'eventlistener':
            case 'listener':
                $type = 'evlistener';
                $header = 'Creating a new event listener';
                $return_text = 'Created event listener';
                break;
            case 'db':
            case 'table':
            case 'migration':
                $type = 'migration';
                $header = 'Creating a new database migration';
                $return_text = 'Created migration';
                break;
            case 'model':
            case 'mod':
                $type = 'model';
                $header = 'Creating a new model';
                $return_text = 'Created model';
                break;
            case 'cm':
            case 'method':
                return $this->addMethodToController();
            default:
                $this->io->error('❌ Invalid type provided. See help for possible values.');
                return false;
        }

        $ch = ConsoleHelper::createAndHandle($this->input, $this->output,
            $this->getHelper('question'),
            $type,
            $header);

        if ($ch === false) {
            return false;
        }

        C::CharmCreator()->createFile($type, $ch->getAbsolutePath(), $ch->getData(), $ch->getTemplate());

        $this->io->writeln('');
        $this->io->success('✅ ' . $return_text . ' ' . $ch->getName());
        $this->io->writeln('');

        return true;
    }

    public function addMethodToController(): bool
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