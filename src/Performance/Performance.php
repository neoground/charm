<?php
/**
 * This file contains the Performance class
 */

namespace Charm\Performance;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Performance
 *
 * The Performance class provides methods for tracking metrics.
 */
class Performance extends Module implements ModuleInterface
{
    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {

    }

    /**
     * Get the microsecond precise timestamp when the app started.
     *
     * @return float|false The time as a floating-point number if it exists, false otherwise.
     */
    public function getStartTime(): float|false
    {
        return C::AppStorage()->get('Charm', 'time_start', false);
    }

    /**
     * Get the microsecond precise timestamp when the app init process has finished.
     *
     * @return float|false The time as a floating-point number if it exists, false otherwise.
     */
    public function getInitDoneTime(): float|false
    {
        return C::AppStorage()->get('Charm', 'time_init', false);
    }

    /**
     * Get the microsecond precise timestamp when the routing init has finished.
     *
     * @return float|false The time as a floating-point number if it exists, false otherwise.
     */
    public function getRoutingDoneTime(): float|false
    {
        return C::AppStorage()->get('Charm', 'time_routing', false);
    }

    /**
     * Get the microsecond precise timestamp when the post init process has finished.
     *
     * This is right before the controller is called.
     *
     * @return float|false The time as a floating-point number if it exists, false otherwise.
     */
    public function getPostInitDoneTime(): float|false
    {
        return C::AppStorage()->get('Charm', 'time_postinit', false);
    }

    /**
     * Get the microsecond precise timestamp when the controller has finished.
     *
     * This is after routing and controller method execution and before the result is outputted.
     *
     * @return float|false The time as a floating-point number if it exists, false otherwise.
     */
    public function getControllerDoneTime(): float|false
    {
        return C::AppStorage()->get('Charm', 'time_controller', false);
    }

    /**
     * Get the microsecond precise timestamp when the output has finished.
     *
     * This is after outputting the result of the controller and before the app shutdown starts.
     * You can't access this value inside a controller or in the main runtime. It is recommended
     * to fetch this value in a shutdown event listener when you want to process this metric as well.
     *
     * @return float|false The time as a floating-point number if it exists, false otherwise.
     */
    public function getOutputDoneTime(): float|false
    {
        return C::AppStorage()->get('Charm', 'time_output', false);
    }

    /**
     * Get the duration between app start and when the app init process has finished.
     *
     * @return float|false The duration of the metric, or false if the duration cannot be calculated.
     */
    public function getStartDuration(): float|false
    {
        return $this->getDuration($this->getStartTime(), $this->getInitDoneTime());
    }

    /**
     * Get the duration between the app init process and when the routing init has finished.
     *
     * @return float|false The duration in seconds, or false if the duration could not be calculated.
     */
    public function getRoutingDuration(): float|false
    {
        return $this->getDuration($this->getInitDoneTime(), $this->getRoutingDoneTime());
    }

    /**
     * Get the duration between the routing init and when the post init process has finished.
     *
     * @return float|false The duration in seconds, or false if the duration could not be calculated.
     */
    public function getPostInitDuration(): float|false
    {
        return $this->getDuration($this->getRoutingDoneTime(), $this->getPostInitDoneTime());
    }

    /**
     * Get the duration between the post init and when the controller method has finished.
     *
     * @return float|false The duration in seconds, or false if the duration could not be calculated.
     */
    public function getControllerDuration(): float|false
    {
        return $this->getDuration($this->getPostInitDoneTime(), $this->getControllerDoneTime());
    }

    /**
     * Get the duration between the controller method return and the finished outputting,
     * right before the app shuts down.
     *
     * You can't access this value inside a controller or in the main runtime. It is recommended
     * to fetch this value in a shutdown event listener when you want to process this metric as well.
     *
     * @return float|false The duration in seconds, or false if the duration could not be calculated.
     */
    public function getOutputDuration(): float|false
    {
        return $this->getDuration($this->getControllerDoneTime(), $this->getOutputDoneTime());
    }

    /**
     * Get the total runtime between app start and output (server response time).
     *
     * If available, this calculates the runtime until OutputDoneTime, if not, ControllerDoneTime is used.
     *
     * @return float|false The duration in seconds, or false if the duration could not be calculated.
     */
    public function getTotalRuntime(): float|false
    {
        $y = $this->getOutputDoneTime();
        if (!$y) {
            $y = $this->getControllerDoneTime();
        }
        return $this->getDuration($this->getStartTime(), $y);
    }

    /**
     * Calculate the duration between two given timestamps.
     *
     * @param float|false $x The first timestamp.
     * @param float|false $y The second timestamp.
     *
     * @return float|false The duration in seconds, or false if the duration could not be calculated.
     */
    private function getDuration(float|false $x, float|false $y): float|false
    {
        if ($x === false || $y === false) {
            return false;
        }
        return $y - $x;
    }

    /**
     * Starts a new metric.
     *
     * @return Metric The started metric instance.
     */
    public function startNewMetric(): Metric
    {
        return (new Metric())->start();
    }

    /**
     * Get the peak memory usage in bytes.
     *
     * @return int The amount of memory currently being used in bytes.
     */
    public function getMemoryUsage(): int
    {
        return memory_get_peak_usage();
    }

    /**
     * Get the total peak memory usage of the application.
     *
     * This returns the total memory allocated from system,
     * including unused pages.
     *
     * @return int The total memory usage in bytes.
     */
    public function getTotalMemoryUsage(): int
    {
        return memory_get_peak_usage(true);
    }

}