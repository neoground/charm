<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Crown\Crown;
use Charm\Vivid\Charm;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RoutesToControllerCommand
 *
 * Creating controller methods and files out of routes
 *
 * @package Charm\CharmCreator\Jobs\Console
 */
class RoutesToControllerCommand extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("cc:route2ctrl")
            ->setDescription("Creating controller methods and files out of app routes");
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
        Charm::CharmCreator()->routesToControllerMethods($output);
        return true;
    }
}