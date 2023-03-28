<?php
/**
 * This file contains a console command.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Bob\Command;
use Charm\Bob\CommandHelper;
use Charm\Vivid\C;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ModuleInstall
 *
 * CLI command to install a new module
 */
class ModuleInstall extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("cm:i")
            ->setDescription("Install a charm module")
            ->setHelp('This command allows you to install a new charm module')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the new charm module');
    }

    /**
     * The execution
     *
     * @param InputInterface   $input
     * @param OutputInterface  $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ch = new CommandHelper($input, $output);

        $name = $input->getArgument('name');

        $output->writeln('<info>Installing ' . $name . '...</info>');

        // TODO Require via composer

        $installed_file = C::Storage()->getBasePath() . DS . 'vendor' . DS . 'composer' . DS . 'installed.php';
        if(!file_exists($installed_file)) {
            $ch->error('❌ Installed composer packages could not be detected!');
            $output->writeln('Make sure you ran "composer install" and that the "vendor" dir exists');
            return self::FAILURE;
        }
        $installed = require($installed_file);

        if(!array_key_exists($name, $installed['versions'])) {
            $ch->error('❌ Module package could not be found!');
            $output->writeln('Make sure you install the package first via: "composer require ' . $name . '"');
            return self::FAILURE;
        }

        $install_path = $installed['versions'][$name]['install_path'];

        $arr = explode("/../", $install_path);
        $rel_path = array_pop($arr);
        $abs_path = C::Storage()->getBasePath() . DS . 'vendor' . DS . $rel_path;

        $module_manifest_path = $abs_path . DS . 'charm.yaml';

        if(!file_exists($module_manifest_path)) {
            $ch->error('❌ Module manifest could not be found!');
            $output->writeln('Make sure you installed the right package. Looked for manifest at: ' . $module_manifest_path);
            return self::FAILURE;
        }

        $manifest = Yaml::parseFile($module_manifest_path);

        $output->writeln('✅ Detected module: ' . $manifest['name'] . ' ' . $manifest['version']);
        $output->writeln('                     ' . $manifest['summary']);

        // Add link in modules.yaml
        $myaml_path = C::Storage()->getAppPath() . DS . 'Config' . DS . 'modules.yaml';

        if(!file_exists($myaml_path)) {
            // Default yaml content
            $myaml = "# +-------------------------------------------------------------------------+\n";
            $myaml .= "# | modules.yaml - The Galactic Registry of Intergalactic Modules           |\n";
            $myaml .= "# +-------------------------------------------------------------------------+\n\n";
            $myaml .= "modules:";
        } else {
            $myaml = file_get_contents($myaml_path);
        }

        $myaml = trim($myaml);

        // Append new module
        $myaml .= "\n  # " . trim($manifest['name']);
        $myaml .= "\n  - " . trim($manifest['binding']);

        if(file_put_contents($myaml_path, $myaml)) {
            $output->writeln('✅ Linked module in modules.yaml');
        } else {
            $ch->error('❌ Could not save modules.yaml file!');
            return self::FAILURE;
        }

        // TODO Execute post install command of module

        $ch->success('[OK] Module ' . $manifest['name'] . ' was installed successfully!');

        return self::SUCCESS;
    }
}