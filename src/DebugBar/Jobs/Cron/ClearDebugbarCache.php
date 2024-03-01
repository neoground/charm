<?php
/**
 * This file contains the ClearDebugbarCache cron job
 */

namespace Charm\DebugBar\Jobs\Cron;

use Charm\Crown\Cronjob;
use Charm\Vivid\C;

/**
 * Class ClearDebugbarCache
 *
 * Cleaning up the debug bar cache regulary and remove files older than 2 days
 *
 * @package Charm\DebugBar\Jobs\Cron
 */
class ClearDebugbarCache extends Cronjob
{
    /**
     * Cron job configuration
     */
    protected function configure(): void
    {
        $this->setName('Cleaning up the debug bar cache')
            ->runDaily(2, 15);
    }

    /**
     * Run that job.
     *
     * @return bool
     */
    public function run(): bool
    {
        $path = C::Storage()->getCachePath() . DS . 'debugbar';
        $keep_in_days = C::Config()->get('main:debug.log_keep_days', 14);
        $now = time();
        if (file_exists($path)) {
            foreach (C::Storage()->scanDir($path) as $file) {
                $absfile = $path . DS . $file;
                if ($now - filemtime($absfile) >= 60 * 60 * 24 * $keep_in_days) {
                    C::Logging()->debug('Removing old debugbar cache file: ' . $file);
                    unlink($absfile);
                }
            }
        }
        return true;
    }
}