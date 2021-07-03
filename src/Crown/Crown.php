<?php
/**
 * This file contains the Crown class
 */

namespace Charm\Crown;

use Carbon\Carbon;
use Charm\Crown\Exceptions\InvalidCronjobException;
use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Handler;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
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
     */
    public function run()
    {
        if (C::has('Event')) {
            C::Event()->fire('Crown', 'run');
        }

        C::Logging()->debug('Running cron jobs');
        if($this->output) {
            $this->output->writeln('<info>Runninig cron jobs</info>');
        }

        // Go through all modules
        $handler = Handler::getInstance();
        foreach ($handler->getModuleClasses() as $name => $module) {
            try {
                $mod = $handler->getModule($name);
                if (is_object($mod) && method_exists($mod, 'getReflectionClass')) {
                    $dir = $mod->getBaseDirectory() . DS . 'Jobs' . DS . 'Cron';
                    $namespace = $mod->getReflectionClass()->getNamespaceName() . "\\Jobs\\Cron";

                    if (file_exists($dir)) {
                        $this->checkCronJobs($dir, $namespace);
                    }
                }
            } catch (\Exception $e) {
                // Cron job error?
                // Just continue, it's logged...
            }
        }
    }

    /**
     * Check cron jobs of a module and execute them
     *
     * @param string $dir the directory where cron jobs are found
     * @param string $namespace the namespace of the cron jobs
     *
     * @throws InvalidCronjobException
     */
    private function checkCronJobs($dir, $namespace)
    {
        // Go through all cron jobs
        foreach (C::Storage()->scanDir($dir) as $file) {
            $fullpath = $dir . DS . $file;
            $pathinfo = pathinfo($fullpath);
            require_once($fullpath);

            $class = $namespace . "\\" . $pathinfo['filename'];

            // Job existing?
            if (!class_exists($class)) {
                // Job (class) not existing!
                C::Logging()->notice('[CROWN] Got invalid job. Class not existing: ' . $class);
                if ($this->output) {
                    $this->output->writeln('<error>Got invalid job. Class not existing: ' . $class . '</error>');
                }

                continue;
            }

            /** @var Cronjob $job */
            $job = new $class;

            // Job must extend Cronjob
            if (!is_subclass_of($job, Cronjob::class)) {
                throw new InvalidCronjobException("Job must extend the Cronjob class");
            }

            // Is job due in this minute?
            $cron = new CronExpression($job->getExpression());
            if ($cron->isDue()) {
                // Yup. Run it!
                $this->executeCronJob($job);
            }
        }
    }

    /**
     * Execute a due cron job
     *
     * @param Cronjob $job
     */
    private function executeCronJob($job)
    {
        C::Logging()->info('[CROWN] Running job: ' . get_class($job) . ' - ' . $job->getName());

        if ($this->output) {
            $this->output->writeln('[' . Carbon::now()->toDateTimeString() . '] Running: ' . $job->getName());
        }

        try {
            $ret = $job->run();

            if (!$ret) {
                // Job didn't run successful
                C::Logging()->warning('[CROWN] Job exited with false: ' . $job->getName());
                if ($this->output) {
                    $this->output->writeln('<error>Job exited with false: ' . $job->getName() . '</error>');
                }
            }

        } catch (\Exception $e) {
            // Log exception
            C::Logging()->error('[CROWN] Exception', [$e->getMessage()]);
            if ($this->output) {
                $this->output->writeln('<error> Exception: ' . $e->getMessage() . '</error>');
            }
        }
    }

}