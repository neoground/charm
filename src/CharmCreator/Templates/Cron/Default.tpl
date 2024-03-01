---
name: Default cron job
fields:
  JOB_NAME:
    name: Name of cron job
    type: input
  JOB_DESCRIPTION:
    name: Short description
    type: input
  JOB_PERIOD:
    name: Period
    type: choice
    choices: EveryQuarterHour,EveryHalfHour,Hourly,Daily,Weekly,EveryMonday,EveryTuesday,EveryWednesday,EveryThursday,EveryFriday,EverySaturday,EverySunday,Monthly,Quarterly,Yearly
    default: 3
---
<?php
/**
 * This file contains a cron job
 */

namespace App\Jobs\Cron;

use Charm\Crown\Cronjob;
use Charm\Vivid\C;

/**
 * Class JOB_NAME
 *
 * JOB_DESCRIPTION
 */
class JOB_NAME extends Cronjob
{
    /**
     * Cron job configuration
     */
    protected function configure(): void
    {
        $this->setName('JOB_DESCRIPTION')
            ->runJOB_PERIOD(0, 0);
    }

    /**
     * Run that job.
     *
     * @return bool
     */
    public function run(): bool
    {
        // TODO

        return true;
    }

}