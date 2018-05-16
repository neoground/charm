<?php
/**
 * This file contains the Crown class
 */

namespace Charm\Crown;

use Carbon\Carbon;
use Charm\Crown\Exceptions\InvalidCronjobException;
use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\PathFinder;
use Cron\CronExpression;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Crown
 *
 * Module binding to Charm kernel
 *
 * @package Charm\Crown
 */
class Crown extends Module implements ModuleInterface
{
    /** @var OutputInterface */
    private $output;

    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // We don't want to slow down the system too much. So cron jobs are only loaded on execution.
    }

    /**
     * Set the optional console output
     *
     * @param OutputInterface $output
     */
    public function setConsoleOutput($output)
    {
        $this->output = $output;
    }

    /**
     * Run all due jobs
     *
     * @throws InvalidCronjobException
     */
    public function run()
    {
        // Get app jobs
        $dir = PathFinder::getAppPath() . DIRECTORY_SEPARATOR . 'Jobs' . DIRECTORY_SEPARATOR . 'Cron';
        $files = array_diff(scandir($dir), ['..', '.']);

        // Go through all cron jobs
        foreach ($files as $file) {
            $fullpath = $dir . DIRECTORY_SEPARATOR . $file;
            $pathinfo = pathinfo($fullpath);
            require_once($fullpath);

            $class = "\\App\\Jobs\\Cron\\" . $pathinfo['filename'];

            // Job existing?
            if(!class_exists($class)) {
                // Job (class) not existing!
                Charm::Logging()->notice('[CROWN] Got invalid job. Class not existing: ' . $class);
                if($this->output) {
                    $this->output->writeln('<error>Got invalid job. Class not existing: ' . $class . '</error>');
                }

                continue;
            }

            /** @var Cronjob $job */
            $job = new $class;

            // Job must extend Cronjob
            if(!is_subclass_of($job, Cronjob::class)) {
                throw new InvalidCronjobException("Job must extend the Cronjob class");
            }

            // Is job due in this minute?
            $cron = CronExpression::factory($job->getExpression());
            if($cron->isDue()) {
                // Yup. Run it!
                Charm::Logging()->info('[CROWN] Running job: ' . $job->getName());

                if($this->output) {
                    $this->output->writeln('[' . Carbon::now()->toDateTimeString() . '] Running: ' . $job->getName());
                }

                try {
                    $ret = $job->run();

                    if(!$ret) {
                        // Job didn't run successful
                        Charm::Logging()->warning('[CROWN] Job exited with false: ' . $job->getName());
                        if($this->output) {
                            $this->output->writeln('<error>Job exited with false: ' . $job->getName() . '</error>');
                        }
                    }

                } catch(\Exception $e) {
                    // Log exception
                    Charm::Logging()->error('[CROWN] Exception', [$e->getMessage()]);
                    if($this->output) {
                        $this->output->writeln('<error> Exception: ' . $e->getMessage() . '</error>');
                    }
                }
            }

        }

    }

}