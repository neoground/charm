<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Bob\Command;
use Charm\CharmCreator\Jobs\ConsoleHelper;
use Charm\Vivid\C;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $controllerName = $input->getOption('ctrl');
        if (empty($controllerName)) {
            // TODO Allow selection for better UX
            $controllerName = $this->ask($input, $output, 'Enter the name of the controller class: ');
        }

        // Validate selected controller
        $controllerName = trim(str_replace(".php", "", $controllerName));
        if (empty($controllerName)) {
            $output->writeln('<error>Invalid controller name provided!</error>');
            return self::FAILURE;
        }

        $path = C::Storage()->getAppPath() . DS . 'Controllers' . DS . str_replace(".", DS, $controllerName) . '.php';

        if (!file_exists($path)) {
            $output->writeln('<error>Controller file not existing!</error>');
            return self::FAILURE;
        }

        // Ask for template + custom fields
        $ch = ConsoleHelper::create($input, $output, $this->getHelper('question'), 'method');
        $ch->askForTemplateAndData();

        C::CharmCreator()->addMethodToController($path, $ch->getData(), $ch->getTemplate());

        $output->writeln('✅ Added method ' . $ch->getData()['METHOD_NAME'] . ' to controller ' . $controllerName);

        return self::SUCCESS;
    }
}