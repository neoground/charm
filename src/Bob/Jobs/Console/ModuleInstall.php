<?php
/**
 * This file contains a console command.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Bob\Command;
use Charm\Vivid\C;
use Symfony\Component\Console\Input\InputArgument;
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
        $this->setName("c:mod")
            ->setDescription("Install / uninstall a charm module")
            ->setHelp('This command allows you to install / uninstall a charm module')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the charm module');
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $name = $this->io->getArgument('name');

        $this->io->writeln('<info>Installing ' . $name . '...</info>');

        $installed_file = C::Storage()->getBasePath() . DS . 'vendor' . DS . 'composer' . DS . 'installed.php';
        if (!file_exists($installed_file)) {
            $this->io->error('<error>❌ Installed composer packages could not be detected!</error>');
            $this->io->writeln('Make sure you ran "composer install" and that the "vendor" dir exists.');
            return false;
        }
        $installed = require($installed_file);

        if (!array_key_exists($name, $installed['versions'])) {
            // TODO Require via composer

            $this->io->error('<error>❌ Module package could not be found!</error>');
            $this->io->writeln('Make sure you install the package first via: "composer require ' . $name . '".');
            return false;
        }

        $install_path = $installed['versions'][$name]['install_path'];

        $arr = explode("/../", $install_path);
        $rel_path = array_pop($arr);
        $abs_path = C::Storage()->getBasePath() . DS . 'vendor' . DS . $rel_path;

        $module_manifest_path = $abs_path . DS . 'charm.yaml';

        if (!file_exists($module_manifest_path)) {
            $this->io->error('<error>❌ Module manifest could not be found!</error>');
            $this->io->writeln('Make sure you installed the right package. Looked for manifest at: ' . $module_manifest_path);
            return false;
        }

        $manifest = Yaml::parseFile($module_manifest_path);

        $this->io->writeln('✅ Detected module: ' . $manifest['name'] . ' ' . $manifest['version']);
        $this->io->writeln('                    ' . $manifest['summary']);

        // Add link in modules.yaml
        $myaml_path = C::Storage()->getAppPath() . DS . 'Config' . DS . 'modules.yaml';

        if (!file_exists($myaml_path)) {
            // Default yaml content
            $myaml = "# +-------------------------------------------------------------------------+\n";
            $myaml .= "# | modules.yaml - The Galactic Registry of Intergalactic Modules           |\n";
            $myaml .= "# +-------------------------------------------------------------------------+\n\n";
            $myaml .= "modules:";
        } else {
            $myaml = file_get_contents($myaml_path);
        }

        $myaml = trim($myaml);

        if(!str_contains($myaml, trim($manifest['name']))) {
            // Not installed yet -> install

            // Append new module
            $myaml .= "\n  # " . trim($manifest['name']);
            $myaml .= "\n  - " . trim($manifest['binding']);

            if (file_put_contents($myaml_path, $myaml)) {
                $this->io->writeln('✅ Linked module in modules.yaml');
            } else {
                $this->io->error('<error>❌ Could not save modules.yaml file!</error>');
                return false;
            }

            // TODO Execute post install command of module

            $this->io->success('✅ Module ' . $manifest['name'] . ' has been installed successfully!');
        } else {
            // Module existing -> ask to uninstall
            if($this->io->confirm('The module is currently installed. Do you want to remove it?')) {
                // Remove line from $myaml which contains $manifest['name']
                $myaml = preg_replace('/\s*# ' . preg_quote(trim($manifest['name']), '/') . '.*\n\s*-\s*' . preg_quote(trim($manifest['binding']), '/') . '/s', '', $myaml);

                if (file_put_contents($myaml_path, $myaml)) {
                    $this->io->writeln('✅ Removed module in modules.yaml');
                } else {
                    $this->io->error('<error>❌ Could not save modules.yaml file!</error>');
                    return false;
                }

                // TODO Execute post uninstall command of module

                // TODO remove from composer

                $this->io->success('✅ Module ' . $manifest['name'] . ' has been removed successfully!');
            }
        }

        return true;
    }
}