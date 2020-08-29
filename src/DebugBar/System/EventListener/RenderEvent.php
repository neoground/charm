<?php
/**
 * This file contains an event listener.
 */

namespace Charm\DebugBar\System\EventListener;

use Charm\Events\EventListener;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Output\View;

/**
 * Class RenderEvent
 *
 * Adding debugbar data to view on render start
 *
 * @package App\System\Events
 */
class RenderEvent extends EventListener
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->fireOnEvent("View", "renderStart");
    }

    /**
     * Event execution
     */
    protected function fire()
    {
        View::addHead(C::DebugBar()->getRenderHead());
        View::addBody(C::DebugBar()->getRenderBar());
    }
}