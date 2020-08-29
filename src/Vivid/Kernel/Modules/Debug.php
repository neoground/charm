<?php
/**
 * This file contains the init class for debugging.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;
use Charm\Vivid\Helper\ModuleDescriber;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use DebugBar\StandardDebugBar;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Whoops\Util\Misc;

/**
 * Class Debug
 *
 * Debug module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Debug extends Module implements ModuleInterface
{
    /** @var StandardDebugBar debug bar object */
    protected $debugbar;

    /**
     * Load the module
     */
    public function loadModule()
    {
        // Only init debug modules if we're in debug mode
        if(Charm::Config()->get('main:debug.debugmode', false)) {
            $this->initWhoops();
        }
    }

    /**
     * Init whoops error page
     *
     * @return bool
     */
    private function initWhoops()
    {
        if(!class_exists("Whoops\\Run")) {
            return false;
        }

        $whoops = new Run;
        $handle = new PrettyPageHandler;

        $handle->setPageTitle("Whoops! Charm Error");

        $whoops->pushHandler($handle);

        // JSON output for AJAX request
        if (Misc::isAjaxRequest()) {
            $whoops->pushHandler(new JsonResponseHandler);
        }

        $whoops->register();
        return true;
    }

}