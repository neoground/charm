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
     * @return bool
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

        $method_name = $this->ask($input, $output, 'Enter the name of the method class: ');
        $method_args = $this->ask($input, $output, 'Enter its arguments in PHP-style: ');
        $method_route = $this->ask($input, $output, 'Enter the name of the route: ');
        $method_url = $this->ask($input, $output, 'Enter the relative URL: ');
        $method_http = strtoupper($this->choice($input, $output, 'Select HTTP method:', ['GET', 'POST', 'PUT', 'DELETE'], 0));

        // Get filters. Can be single one or multiple with commas separated, make it the right form
        $method_filter = $this->ask($input, $output, 'Enter the wanted route filters (e.g. guard:auth): ');
        if (!str_contains($method_filter, ',')) {
            // Single one
            $method_filter_str = '"' . $method_filter . '"';
        } else {
            $method_filter_str = '[';
            foreach (explode(",", $method_filter) as $mf) {
                $method_filter_str .= '"' . trim($mf) . '",';
            }
            $method_filter_str = rtrim($method_filter_str, ",") . ']';
        }

        // TODO Allow custom fields depending on template

        $data = [
            'METHOD_NAME' => $method_name,
            '$METHOD_ARGS' => $method_args,
            'METHOD_ROUTE' => $method_route,
            'METHOD_URL' => $method_url,
            'METHOD_HTTP' => $method_http,
            'METHOD_FILTER' => $method_filter_str,
        ];

        C::CharmCreator()->addMethodToController($path, $data, $template);

        $output->writeln('✅ Added method ' . $data['METHOD_NAME'] . ' to controller ' . $controllerName);

        return true;
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