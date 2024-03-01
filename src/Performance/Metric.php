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
     * @return self Returns an instance of this class.
     */
    public function start(): self
    {
        $this->start_time = microtime(true);
        return $this;
    }

    /**
     * End the measurement.
     *
     * @return self Returns an instance of this class.
     */
    public function end(): self
    {
        $this->end_time = microtime(true);
        return $this;
    }

    /**
     * Get the duration in seconds.
     *
     * @return float The duration in seconds with microsecond precision.
     */
    public function getDuration(): float
    {
        return $this->end_time - $this->start_time;
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