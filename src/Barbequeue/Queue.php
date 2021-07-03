<?php
/**
 * This file contains the Queue class
 */

namespace Charm\Barbequeue;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Queue
 *
 * Module binding to Charm kernel
 *
 * @package Charm\Barbequeue
 */
class Queue extends Module implements ModuleInterface
{
    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // Nothing to do here yet.
    }

    /**
     * Push a new job to the queue
     *
     * @param QueueEntry $entry the entry
     * 
     * @return bool
     */
    public function push(QueueEntry $entry)
    {
        // Get queue
        $queue_name = $entry->getQueueName(true);

        // Set the job content
        $job_content = [
            'method' => $entry->getMethod(),
            'args' => $entry->getArguments()
        ];

        if (C::Config()->inDebugMode()) {
            C::Logging()->debug('[BBQ] Pushing job to queue: ' . $entry->getMethod());
        }

        if(C::has('Event')) {
            C::Event()->fire('Queue', 'push');
        }

        // Add it to the queue in redis
        return C::Redis()->rpush($queue_name, json_encode($job_content));
    }

    /**
     * Run the queue!
     *
     * @param string $name      the queue name
     * @param int    $worker_id optional id of worker
     *
     * @return bool
     */
    public function run($name, $worker_id = 1)
    {
        if (empty($worker_id) || !is_numeric($worker_id)) {
            $worker_id = 1;
        }

        if(C::has('Event')) {
            C::Event()->fire('Queue', 'run');
        }

        $this->doWork($name, $worker_id);
        return true;
    }

    /**
     * Clear a queue
     *
     * @param string $name the queue's name
     */
    public function clear($name)
    {
        // Get prefix
        $prefix = C::Config()->get('main:bbq.name',
            C::Config()->get('main:session.name', 'charm')
        );

        for ($priority = 1; $priority <= 5; $priority++) {
            // Get queue name
            $queue = $prefix . '-' . strtolower($name) . '-p' . $priority;

            C::Redis()->del($queue);
        }
    }

    /**
     * Worker method. Do the work!
     *
     * @param string $name      the queue name
     * @param int    $worker_id optional id of worker
     */
    private function doWork($name, $worker_id = 1)
    {
        // Get prefix
        $prefix = C::Config()->get('main:bbq.name',
            C::Config()->get('main:session.name', 'charm')
        );

        for ($priority = 1; $priority <= 5; $priority++) {
            // Get queue name
            $queue = $prefix . '-' . strtolower($name) . '-p' . $priority;

            $count = C::Redis()->getClient()->llen($queue);

            if($count > 0) {
                C::Logging()->info('[BBQ] Starting queue ' . $queue . ' - Got ' . $count . ' jobs');
            }

            // Work as long as there are elements left in this queue
            while (C::Redis()->getClient()->llen($queue) > 0) {
                // Get first element. This is our job!
                $job = C::Redis()->getClient()->lpop($queue);

                // And execute the job!
                $this->executeJob($job, $worker_id);
            }
        }

        if(C::has('Event')) {
            C::Event()->fire('Queue', 'done');
        }

        C::Logging()->debug('Worker ' . $worker_id . ' done! Terminating.');
    }

    /**
     * Execute a single job
     *
     * @param  string  $job        json string of job
     * @param  int     $worker_id  id of worker
     */
    private function executeJob($job, $worker_id)
    {
        C::Logging()->debug('[BBQ] [Worker ' . $worker_id . '] Running: ' . $job);

        // Get the job data
        if(is_serialized($job)) {
            $job = unserialize($job);
        }

        $job_data = json_decode($job, true);

        // Execute!
        try {
            $ret = null;
            if(!empty($job_data['method'])) {
                $ret = call_user_func_array($job_data['method'], $job_data['args']);
            }

        } catch (\Exception $e) {
            C::Logging()->error('[BBQ] Exception: ' . $e->getMessage());
            $ret = false;
        }

        if ($ret === false) {
            // Error for this job
            C::Logging()->error('[BBQ] Error for job: ' . $job_data['method'] .
                '; Args: ' . json_encode($job_data['args']));

        }
    }

}