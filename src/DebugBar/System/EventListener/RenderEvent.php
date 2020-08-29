<?php
/**
 * This file contains an event listener.
 */

namespace Charm\DebugBar\System\EventListener;

use Charm\Events\EventListener;
use Charm\Vivid\C;
use Charm\Vivid\Charm;
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
    public function fire()
    {
        if(Charm::Config()->get('main:debug.show_debugbar', false)) {
            $time_start = C::AppStorage()->get('Charm', 'time_start');
            $time_init = C::AppStorage()->get('Charm', 'time_init');
            $time_routing = C::AppStorage()->get('Charm', 'time_routing');
            $time_controller = C::AppStorage()->get('Charm', 'time_controller');

            // Add time measurements
            if(!empty($time_start) && !empty($time_init) && !empty($time_routing)) {
                C::DebugBar()->getInstance()['time']->addMeasure('Startup', $time_start, $time_init);
                C::DebugBar()->getInstance()['time']->addMeasure('Routing', $time_init, $time_routing);
                C::DebugBar()->getInstance()['time']->addMeasure('Controller', $time_routing, $time_controller);
            }

            // Add debugbar to head + body
            View::addHead('debugbar_head', C::DebugBar()->getRenderHead());
            View::addBody('debugbar_body', C::DebugBar()->getRenderBar());
        }
    }
}