<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Vivid\C;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class CreateControllerCommand
 *
 * Creating controller file
 */
class CreateControllerCommand extends Command
{
    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("c:c")
            ->setDescription("Creating a controller")
            ->setHelp('This command allows you to add a new controller.');
    }

    /**
     * The execution
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $controllerQuestion = new Question('Enter the name of the controller class: ');
        $controllerName = $helper->ask($input, $output, $controllerQuestion);

        $controllerName = trim($controllerName);
        if (empty($controllerName)) {
            $output->writeln('<error>Invalid controller name provided!</error>');
            return self::FAILURE;
        }

        $available_templates = C::CharmCreator()->getAvailableTemplates('controller');

        $question = new ChoiceQuestion('Select wanted template:', $available_templates);
        $template = $this->getHelper('question')->ask($input, $output, $question);

        $namespace = "App\\Controllers";

        $dir = C::Storage()->getAppPath() . DS . 'Controllers';

        if (str_contains($controllerName, '.')) {
            $parts = explode('.', $controllerName);

            // Name is last part
            $controllerName = array_pop($parts);

            // Use rest parts for namespace
            $dir .= DS . implode(DS, $parts);
            $namespace .= "\\" . implode("\\", $parts);
        }

        $data = [
            'CLASSNAME' => $controllerName,
            'CLASSNAMESPACE' => $namespace,
        ];

        $filename = $controllerName . '.php';

        if (file_exists($filename)) {
            $output->writeln('<error>Controller file already existing!</error>');
            return self::FAILURE;
        }

        C::Storage()->createDirectoriesIfNotExisting($dir);

        C::CharmCreator()->createController($dir . DS . $filename, $data, $template);

        $output->writeln('✅ Created model ' . $filename);

        return true;
    }
}