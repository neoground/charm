<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Carbon\Carbon;
use Charm\Vivid\Charm;
use Charm\Vivid\PathFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateMigrationCommand
 *
 * Creating migration files
 *
 * @package Charm\CharmCreator\Jobs\Console
 */
class CreateMigrationCommand extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("cc:migration")
            ->setDescription("Creating a migration file")
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
        $table_name_formatted = str_replace("_", "", $table_name_formatted) . "Table";

        $data = [
            'TABLECLASSNAME' => $table_name_formatted,
            'TABLENAME' => $table_name
        ];

        $dir = PathFinder::getAppPath() . DS . 'System' . DS . 'Migrations';

        $date = Carbon::now()->format('Ymd');

        $counter = 0;
        foreach(scandir($dir) as $file) {
            if(in_string($date, $file)) {
                $counter++;
            }
        }

        if($counter == 0) {
            $counter = 10;
        } else {
            $counter = $counter * 10;
        }

        $filename = $date . '_' . $counter . '_' . $table_name . '.php';

        Charm::CharmCreator()->createMigration($dir . DS . $filename, $data, $template);

        $output->writeln('<success>Created migration ' . $filename
            . ' - ' . $table_name_formatted . '</success>');

        return true;
    }
}