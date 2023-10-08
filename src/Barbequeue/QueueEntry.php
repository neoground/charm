<?php
/**
 * This file contains the QueueEntry class
 */

namespace Charm\Barbequeue;

use Charm\Vivid\C;

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
    protected string $queue_name;

    /** @var int the priority */
    protected int $priority;

    /** @var string the callback method */
    protected string $method;

    /** @var array optional arguments */
    protected array $arguments;

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
    public function getQueueName(bool $as_key = false): string
    {
        // Set prefix to support multiple BBQ instances on one redis installation
        $prefix = C::Config()->get('main:bbq.name',
            C::Config()->get('main:session.name', 'charm')
        );

        if ($as_key) {
            return $prefix . '-' . strtolower($this->queue_name) . '-p' . $this->priority;
        }

        return $prefix . '-' . $this->queue_name;
    }

    /**
     * Set queue name
     *
     * @param string $queue_name
     *
     * @return $this
     */
    public function setQueueName(string $queue_name): self
    {
        $this->queue_name = $queue_name;
        return $this;
    }

    /**
     * Get priority
     *
     * @return int
     */
    public function getPriority(): int
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
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Get callback method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get callback method arguments
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param string $method the callback method
     * @param array  $args   (opt.) arguments to pass
     *
     * @return $this
     */
    public function setMethod(string $method, array $args = []): self
    {
        $this->method = $method;
        $this->arguments = $args;
        return $this;
    }

}