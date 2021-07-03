<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Vivid\C;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateModelCommand
 *
 * Creating model files
 *
 * @package Charm\CharmCreator\Jobs\Console
 */
class CreateModelCommand extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("cc:model")
            ->setDescription("Creating a model file")
            ->addArgument(
                'tablearg',
                InputArgument::OPTIONAL,
                'Table name'
            )
            ->addOption(
                'table',
                't',
                InputOption::VALUE_REQUIRED,
                'Table name'
            )->addOption(
                'template',
                'tpl',
                InputOption::VALUE_OPTIONAL,
                'Template name'
            );
    }

    /**
     * The execution
     *
     * @param InputInterface   $input
     * @param OutputInterface  $output
     *
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table_name = $input->getOption('table');
        $template = $input->getOption('template');

        if(empty($table_name)) {
            $table_name = $input->getArgument('tablearg');
        }

        if(empty($template)) {
            $template = 'Default';
        }

        $table_name_formatted = ucwords($table_name, "_");
        $table_name_formatted = str_replace("_", "", $table_name_formatted);

        $data = [
            'CLASSNAME' => $table_name_formatted,
            'TABLENAME' => $table_name
        ];

        $dir = C::Storage()->getAppPath() . DS . 'Models';

        $filename = $table_name_formatted . '.php';

        C::CharmCreator()->createModel($dir . DS . $filename, $data, $template);

        $output->writeln('✅ Created model ' . $filename);

        return true;
    }
}