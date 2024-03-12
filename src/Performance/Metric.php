<?php
/**
 * This file contains the Metric class.
 */

namespace Charm\Performance;

/**
 * Class Metric
 *
 * A single performance metric
 */
class Metric
{
    protected float $start_time;
    protected float $end_time;

    /**
     * Start the measurement.
     *
     * @return static Returns an instance of this class.
     */
    public function start(): static
    {
        return $this->setStartTime(microtime(true));
    }

    /**
     * End the measurement.
     *
     * @return static Returns an instance of this class.
     */
    public function end(): static
    {
        return $this->setEndTime(microtime(true));
    }

    /**
     * Set the start time of the measurement.
     *
     * @param float $time The start time of the measurement.
     *
     * @return static Returns an instance of this class.
     */
    public function setStartTime(float $time): static
    {
        $this->start_time = $time;
        return $this;
    }

    /**
     * Set the end time of the measurement.
     *
     * @param float $time The end time of the measurement.
     *
     * @return static Returns an instance of this class.
     */
    public function setEndTime(float $time): static
    {
        $this->end_time = $time;
        return $this;
    }

    /**
     * Get the duration in seconds.
     *
     * If no end time is set, the current time will be used.
     *
     * @return float The duration in seconds with microsecond precision.
     */
    public function getDuration(): float
    {
        $end = $this->end_time;
        if(empty($end)) {
            $end = microtime(true);
        }

        return $end - $this->start_time;
    }

    /**
     * Get the duration in minutes.
     *
     * @return int The duration in minutes as an integer.
     */
    public function getDurationInMinutes(): int
    {
        return (int)floor($this->getDuration() / 60);
    }

    /**
     * Get the formatted duration in minutes and seconds.
     *
     * @return string The formatted duration string in the format "4m 20s"
     */
    public function getFormattedDuration(): string
    {
        return $this->getDurationInMinutes() . 'm ' . ($this->getDuration() % 60) . 's';
    }
}