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
    protected function configure()
    {
        $this->setName('Cleaning up the debug bar cache')
            ->runDaily(4);
    }

    /**
     * Run that job.
     *
     * @return bool
     */
    public function run()
    {
        $path = C::Storage()->getCachePath() . DS . 'debugbar';
        $now = time();

        if(file_exists($path)) {
            foreach(C::Storage()->scanDirForFiles($path) as $file) {
                // When file is older than 48 hours, delete it
                if ($now - filemtime($file) >= 60 * 60 * 48) {
                    unlink($file);
                }
            }
        }

        return true;
    }
}