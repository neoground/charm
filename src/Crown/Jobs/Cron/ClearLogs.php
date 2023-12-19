<?php
/**
 * This file contains the ClearLogs cron job
 */

namespace Charm\Crown\Jobs\Cron;

use Charm\Crown\Cronjob;
use Charm\Vivid\C;

/**
 * Class ClearLogs
 *
 * Clearing old logs
 *
 * @package Charm\Crown\Jobs\Cron
 */
class ClearLogs extends Cronjob
{
    /**
     * Cron job configuration
     */
    protected function configure(): void
    {
        $this->setName('Clearing old logs')
            ->runDaily(2);
    }

    /**
     * Remove old log files from the storage directory.
     *
     * This method scans the log storage directory and removes log files
     * that are older than the specified number of days determined by the
     * "main:logging.keep_days" configuration value.
     *
     * @return int Returns true if the operation was successful.
     */
    public function run(): int
    {
        $path = C::Storage()->getLogPath();
        $files = C::Storage()->scanDir($path);
        $keep_in_days = C::Config()->get('main:logging.keep_days', 30);
        $now = time();
        if (file_exists($path)) {
            foreach ($files as $file) {
                $absfile = $path . DS . $file;
                if ($now - filemtime($absfile) >= 60 * 60 * 24 * $keep_in_days) {
                    C::Logging()->debug('Removing old log file: ' . $file);
                    unlink($absfile);
                }
            }
        }
        return true;
    }
}