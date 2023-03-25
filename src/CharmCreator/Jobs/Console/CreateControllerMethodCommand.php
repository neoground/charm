<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Vivid\C;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

/**
 * Class CreateControllerMethodCommand
 *
 * Creating method in controller file
 */
class CreateControllerMethodCommand extends Command
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

        // Ask for method details
        $available_templates = C::CharmCreator()->getAvailableTemplates('methods');
        $template = $this->choice($input, $output, 'Select wanted template:', $available_templates, 'Default');

        // TODO Get custom fields from chosen tpl
        $tpl = C::CharmCreator()->getTemplate('method', $template);

        // Extract YAML frontmatter
        $parts = explode("---\n", $tpl);
        $yaml = $parts[1];

        $yaml_arr = Yaml::parse($yaml);

        $data = [];
        foreach($yaml_arr['fields'] as $name => $field) {
            if($field['type'] == 'input') {
                $data[$name] = $this->ask($input, $output, $field['name'] . ': ');
            } elseif($field['type'] == 'choice') {
                $data[$name] = $this->choice($input, $output, $field['name'] . ': ', explode(",", $field['choices']), $field['default']);
            }
        }

        C::CharmCreator()->addMethodToController($path, $data, $template);

        $output->writeln('✅ Added method ' . $data['METHOD_NAME'] . ' to controller ' . $controllerName);

        return self::SUCCESS;
    }

    private function ask($input, $output, $question)
    {
        return $this->getHelper('question')->ask($input, $output, new Question($question));
    }

    private function choice($input, $output, $question, $arr, $default = null)
    {
        $cq = new ChoiceQuestion($question, $arr, $default);
        return $this->getHelper('question')->ask($input, $output, $cq);
    }
}