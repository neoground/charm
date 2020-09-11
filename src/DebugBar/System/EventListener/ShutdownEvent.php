<?php
/**
 * This file contains an event listener.
 */

namespace Charm\DebugBar\System\EventListener;

use Charm\Events\EventListener;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Output\View;

/**
 * Class ShutdownEvent
 *
 * Collecting debug bar data when system shuts down
 *
 * @package App\System\Events
 */
class ShutdownEvent extends EventListener
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->fireOnEvent("Charm", "shutdown");
    }

    /**
     * Event execution
     */
    public function fire()
    {
        // Collect all data in file
        C::DebugBar()->getInstance()->collect();
    }
}