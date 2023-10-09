<?php
/**
 * This file contains the Crown class
 */

namespace Charm\Crown;

use Carbon\Carbon;
use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Handler;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Cron\CronExpression;
use Symfony\Component\Console\Output\NullOutput;
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
    private OutputInterface $output;

    /**
     * Constructor
     *
     * Set null output as default to make output accessible
     */
    public function __construct()
    {
        $this->output = new NullOutput();
    }

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
    public function setConsoleOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Run all due jobs
     */
    public function run(): void
    {
        if (C::has('Event')) {
            C::Event()->fire('Crown', 'run');
        }

        C::Logging()->debug('Running cron jobs');
        $this->output->writeln('<info>Running cron jobs</info>');

        // Collect jobs
        $all_jobs = $this->getAllCronJobs();

        // Check if due
        foreach ($all_jobs as $job) {
            $cron = new CronExpression($job->getExpression());
            if ($cron->isDue()) {
                // Yup. Run it!
                $this->executeCronJob($job);
            }
        }
    }

    /**
     * Get all cron jobs
     *
     * @return Cronjob[] each element is a Cronjob class instance
     */
    public function getAllCronJobs(): array
    {
        $all_jobs = [];

        // Go through all modules
        $handler = Handler::getInstance();
        foreach ($handler->getModuleClasses() as $name => $module) {
            try {
                $mod = $handler->getModule($name);
                if (is_object($mod) && method_exists($mod, 'getReflectionClass')) {

                    // Check if module has Cron dir, if so, load jobs from there
                    $dir = $mod->getBaseDirectory() . DS . 'Jobs' . DS . 'Cron';
                    $namespace = $mod->getReflectionClass()->getNamespaceName() . "\\Jobs\\Cron";

                    $module_jobs = [];
                    if (file_exists($dir)) {
                        $module_jobs = $this->loadCronjobs($dir, $namespace);
                    }

                    foreach ($module_jobs as $mj) {
                        $all_jobs[] = $mj;
                    }
                }
            } catch (\Exception $e) {
                // Cron job error?
                // Just continue, it's logged...
            }
        }

        return $all_jobs;
    }

    /**
     * Load cron jobs in a single directory
     *
     * @param string $dir       absolute path to directory
     * @param string $namespace the namespace in this directory
     *
     * @return array
     */
    private function loadCronjobs(string $dir, string $namespace): array
    {
        $jobs = [];
        // Go through all cron jobs
        foreach (C::Storage()->scanDir($dir) as $file) {
            $fullpath = $dir . DS . $file;
            $pathinfo = pathinfo($fullpath);
            require_once($fullpath);

            $class = $namespace . "\\" . $pathinfo['filename'];

            // Job existing?
            if (!class_exists($class)) {
                // Job (class) not existing, so ignore entry
                $this->output->writeln('Invalid cronjob class: ' . $class, OutputInterface::VERBOSITY_VERBOSE);
                continue;
            }

            /** @var Cronjob $job */
            $job = new $class;

            // Job must extend Cronjob
            if (!is_subclass_of($job, Cronjob::class)) {
                $this->output->writeln('Job must extend the Cronjob class: ' . $class, OutputInterface::VERBOSITY_VERBOSE);
                continue;
            }

            // Get and validate cron expression
            $expression = $job->getExpression();
            if (empty($expression) || !CronExpression::isValidExpression($expression)) {
                $this->output->writeln('Invalid cronjob expression: ' . $expression
                    . ' for job: ' . $job->getName(),
                    OutputInterface::VERBOSITY_VERBOSE);
                continue;
            }

            $this->output->writeln('Loading cronjob: ' . $job->getName()
                . '. Expression: ' . $expression,
                OutputInterface::VERBOSITY_VERBOSE);

            $jobs[] = $job;
        }

        return $jobs;
    }

    /**
     * Execute a due cron job
     *
     * @param Cronjob $job
     */
    private function executeCronJob(Cronjob $job): void
    {
        C::Logging()->info('[CROWN] Running job: ' . get_class($job) . ' - ' . $job->getName());

        $this->output->writeln('[' . Carbon::now()->toDateTimeString() . '] Running: ' . $job->getName());

        try {
            $ret = $job->run();

            if (!$ret) {
                // Job didn't run successful
                C::Logging()->warning('[CROWN] Job exited with false: ' . $job->getName());
                $this->output->writeln('<error>Job exited with false: ' . $job->getName() . '</error>');
            }

        } catch (\Exception $e) {
            // Log exception
            C::Logging()->error('[CROWN] Exception', [$e->getMessage()]);
            $this->output->writeln('<error> Exception: ' . $e->getMessage() . '</error>');
        }
    }

}