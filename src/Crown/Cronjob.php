<?php
/**
 * This file contains the Cronjob class
 */

namespace Charm\Crown;

/**
 * Class Cronjob
 *
 * Defining a cron job
 *
 * @package Charm\Crown
 */
class Cronjob
{
    const SUNDAY = 0;
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;

    /** @var string  name of cron job */
    protected string $name;

    /** @var string  cron expression */
    protected string $expression;

    /**
     * Cronjob constructor.
     */
    public function __construct()
    {
        // On init configure this job!
        $this->configure();
    }

    /**
     * Configuration of the cron job
     */
    protected function configure(): void
    {
    }

    /**
     * Run that job
     *
     * @return bool
     */
    public function run(): bool
    {
        return false;
    }

    /**
     * Set the name
     *
     * @param string $name name of cron job
     *
     * @return $this
     */
    protected function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the name of the cron job
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the cron expression
     *
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * Set the cron expression
     *
     * @param string $expression cron expression
     *
     * @return $this
     */
    protected function setExpression(string $expression): self
    {
        $this->expression = $expression;
        return $this;
    }

    /**
     * Set schedule for this cron job
     *
     * @param string|int|array $min   (opt.) minute or array with minutes or minute expression
     * @param string|int|array $hour  (opt.) hour or array with hours or hour expression
     * @param string|int|array $dom   (opt.) day of month or array with days of month or day of month expression
     * @param string|int|array $month (opt.) number of month or array with months or month expression
     * @param string|int|array $dow   (opt.) day of week or array with days of week or day of week expression
     *
     * @return $this
     */
    protected function setSchedule(mixed $min = '*', mixed $hour = '*', mixed $dom = '*', mixed $month = '*', mixed $dow = '*'): self
    {
        if (is_array($min)) {
            $min = implode(",", $min);
        }

        if (is_array($hour)) {
            $hour = implode(",", $hour);
        }

        if (is_array($dom)) {
            $dom = implode(",", $dom);
        }

        if (is_array($month)) {
            $month = implode(",", $month);
        }

        if (is_array($dow)) {
            $dow = implode(",", $dow);
        }

        return $this->setExpression($min . ' ' . $hour . ' ' . $dom . ' ' . $month . ' ' . $dow);
    }

    // +--------------------------------------------------+
    // |  Cron expression shortcut functions for config   |
    // |  More expressions: https://crontab.guru          |
    // +--------------------------------------------------+

    /**
     * Run this job once every day
     *
     * @param string|int|array $hour (opt.) the hour or array of hours (default: 0)
     * @param string|int|array $min  (opt.) the minute or array of minutes (default: 0)
     *
     * @return $this
     */
    protected function runDaily(mixed $hour = 0, mixed $min = 0): self
    {
        return $this->setSchedule($min, $hour);
    }

    /**
     * Run this job once every hour
     *
     * @param string|int|array $min (opt.) the minute or array of minutes (default: 0)
     *
     * @return $this
     */
    protected function runHourly(mixed $min = 0): self
    {
        return $this->setSchedule($min);
    }

    /**
     * Run this job every 30 minutes
     *
     * @param int $offset (opt.) offset for minutes (called on minute 0 and 30 without offset)
     *
     * @return $this
     */
    protected function runEveryHalfHour(int $offset = 0): self
    {
        $i = $offset;
        $j = 30 + $offset;
        return $this->setSchedule([$i, $j]);
    }

    /**
     * Run this job every 15 minutes
     *
     * @param int $offset (opt.) offset for minutes (called on minute 0, 15, 30, 45 without offset)
     *
     * @return $this
     */
    protected function runEveryQuarterHour(int $offset = 0): self
    {
        $i = $offset;
        $j = 15 + $offset;
        $k = 30 + $offset;
        $l = 45 + $offset;
        return $this->setSchedule([$i, $j, $k, $l]);
    }

    /**
     * Run this job once every week
     *
     * @param string|int|array $day  (opt.) the day of week or array with days (0 - sunday, 6 - saturday, default: 1)
     * @param string|int|array $hour (opt.) the hour or array of hours (default: 0)
     * @param string|int|array $min  (opt.) the minute or array of minutes (default: 0)
     *
     * @return $this
     */
    protected function runWeekly(mixed $day = 1, mixed $hour = 0, mixed $min = 0): self
    {
        return $this->setSchedule($min, $hour, '*', '*', $day);
    }

    /**
     * Run this job every monday
     *
     * @param string|int|array $hour (opt.) the hour or array of hours (default: 0)
     * @param string|int|array $min  (opt.) the minute or array of minutes (default: 0)
     *
     * @return $this
     */
    protected function runEveryMonday(mixed $hour = 0, mixed $min = 0): self
    {
        return $this->runWeekly(self::MONDAY, $hour, $min);
    }

    /**
     * Run this job every tuesday
     *
     * @param string|int|array $hour (opt.) the hour or array of hours (default: 0)
     * @param string|int|array $min  (opt.) the minute or array of minutes (default: 0)
     *
     * @return $this
     */
    protected function runEveryTuesday(mixed $hour = 0, mixed $min = 0): self
    {
        return $this->runWeekly(self::TUESDAY, $hour, $min);
    }

    /**
     * Run this job every wednesday
     *
     * @param string|int|array $hour (opt.) the hour or array of hours (default: 0)
     * @param string|int|array $min  (opt.) the minute or array of minutes (default: 0)
     *
     * @return $this
     */
    protected function runEveryWednesday(mixed $hour = 0, mixed $min = 0): self
    {
        return $this->runWeekly(self::WEDNESDAY, $hour, $min);
    }

    /**
     * Run this job every thursday
     *
     * @param string|int|array $hour (opt.) the hour or array of hours (default: 0)
     * @param string|int|array $min  (opt.) the minute or array of minutes (default: 0)
     *
     * @return $this
     */
    protected function runEveryThursday(mixed $hour = 0, mixed $min = 0): self
    {
        return $this->runWeekly(self::THURSDAY, $hour, $min);
    }

    /**
     * Run this job every friday
     *
     * @param string|int|array $hour (opt.) the hour or array of hours (default: 0)
     * @param string|int|array $min  (opt.) the minute or array of minutes (default: 0)
     *
     * @return $this
     */
    protected function runEveryFriday(mixed $hour = 0, mixed $min = 0): self
    {
        return $this->runWeekly(self::FRIDAY, $hour, $min);
    }

    /**
     * Run this job every saturday
     *
     * @param string|int|array $hour (opt.) the hour or array of hours (default: 0)
     * @param string|int|array $min  (opt.) the minute or array of minutes (default: 0)
     *
     * @return $this
     */
    protected function runEverySaturday(mixed $hour = 0, mixed $min = 0): self
    {
        return $this->runWeekly(self::SATURDAY, $hour, $min);
    }

    /**
     * Run this job every sunday
     *
     * @param string|int|array $hour (opt.) the hour or array of hours (default: 0)
     * @param string|int|array $min  (opt.) the minute or array of minutes (default: 0)
     *
     * @return $this
     */
    protected function runEverySunday(mixed $hour = 0, mixed $min = 0): self
    {
        return $this->runWeekly(self::SUNDAY, $hour, $min);
    }

    /**
     * Run this job once every month
     *
     * @param string|int|array $day  (opt.) the day of month or array with days of month (default: 1)
     * @param string|int|array $hour (opt.) the hour or array of hours (default: 0)
     * @param string|int|array $min  (opt.) the minute or array of minutes (default: 0)
     *
     * @return $this
     */
    protected function runMonthly(mixed $day = 1, mixed $hour = 0, mixed $min = 0): self
    {
        return $this->setSchedule($min, $hour, $day);
    }

    /**
     * Run this job once every quarter (every 3rd month)
     *
     * @param string|int|array $day  (opt.) the day of month or array with days of month (default: 1)
     * @param string|int|array $hour (opt.) the hour or array of hours (default: 0)
     * @param string|int|array $min  (opt.) the minute or array of minutes (default: 0)
     *
     * @return $this
     */
    protected function runQuarterly(mixed $day = 1, mixed $hour = 0, mixed $min = 0): self
    {
        return $this->setSchedule($min, $hour, $day, '*/3');
    }

    /**
     * Run this job once every quarter (every 3rd month)
     *
     * @param string|int|array $month (opt.) the month or array with months (default: 1)
     * @param string|int|array $day   (opt.) the day of month or array with days of month (default: 1)
     * @param string|int|array $hour  (opt.) the hour or array of hours (default: 0)
     * @param string|int|array $min   (opt.) the minute or array of minutes (default: 0)
     *
     * @return $this
     */
    protected function runYearly(mixed $month = 1, mixed $day = 1, mixed $hour = 0, mixed $min = 0): self
    {
        return $this->setSchedule($min, $hour, $day, $month);
    }

}