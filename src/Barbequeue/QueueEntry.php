<?php
/**
 * This file contains the QueueEntry class
 */

namespace Charm\Barbequeue;

/**
 * Class QueueEntry
 *
 * A queue entry
 *
 * @package Charm\Barbequeue
 */
class QueueEntry
{
    /** @var string the queue name */
    protected $queue_name;

    /** @var int the priority */
    protected $priority;

    /** @var string the callback method */
    protected $method;

    /** @var array optional arguments */
    protected $arguments;

    /**
     * QueueEntry constructor.
     */
    public function __construct()
    {
        // Default value for queue name
        $this->queue_name = 'default';
    }

    /**
     * Get queue name
     *
     * @param bool $as_key (opt.) return as key for redis? Default: false
     *
     * @return string
     */
    public function getQueueName($as_key = false)
    {
        if($as_key) {
            return strtolower($this->queue_name) . '-p' . $this->priority;
        }

        return $this->queue_name;
    }

    /**
     * Set queue name
     *
     * @param string $queue_name
     *
     * @return $this
     */
    public function setQueueName($queue_name)
    {
        $this->queue_name = $queue_name;
        return $this;
    }

    /**
     * Get priority
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set priority
     *
     * @param int $priority (1 - high, 5 - low)
     *
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Get callback method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get callback method arguments
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param string $method the callback method
     * @param array  $args   (opt.) arguments to pass
     *
     * @return $this
     */
    public function setMethod($method, $args = [])
    {
        $this->method = $method;
        $this->arguments = $args;
        return $this;
    }

}