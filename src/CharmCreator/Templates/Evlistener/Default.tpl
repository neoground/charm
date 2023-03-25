---
name: Default event listener
fields:
  EVL_NAME:
    name: Name of event listener
    type: input
  EVL_DESC:
    name: Short description
    type: input
  EV_MODULE:
    name: Fire on module
    type: input
  EV_EVENT:
    name: Fire on event
    type: input
---
<?php
/**
 * This file contains an event listener.
 */

namespace App\System\EventListener;

use Charm\Events\EventListener;
use Charm\Vivid\C;

/**
 * Class EVL_NAME
 *
 * EVL_DESC
 */
class EVL_NAME extends EventListener
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->fireOnEvent("EV_MODULE", "EV_EVENT");
    }

    /**
     * Event execution
     */
    public function fire()
    {
        C::Logging()->info('EV_MODULE EV_EVENT fired');
    }

}
