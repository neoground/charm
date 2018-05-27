<?php
/**
 * This file contains the init class for debugging.
 */

namespace Charm\Vivid\Kernel\Modules;

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
class Debug implements ModuleInterface
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
            $this->initDebugBar();
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

    /**
     * Init the debug bar
     *
     * @return bool
     */
    private function initDebugBar()
    {
        // No debug bar if disabled or not installed
        if(!Charm::Config()->get('main:debug.show_debugbar', false)
            || !class_exists("DebugBar\\StandardDebugBar")
        ) {
            return false;
        }

        $this->debugbar = new StandardDebugBar();

        // Append two debug bar methods to twig head / body
        Charm::AppStorage()->append('View', 'Head', ModuleDescriber::create()
            ->setModule('Debug')
            ->setMethod('getDebugBarHead')
        );

        Charm::AppStorage()->append('View', 'Body', ModuleDescriber::create()
            ->setModule('Debug')
            ->setMethod('getDebugBarBody')
        );

        return true;
    }

    /**
     * Get debug bar instance
     *
     * @return StandardDebugBar
     */
    public function getDebugBar()
    {
        return $this->debugbar;
    }

    /**
     * Get debug bar <head> section
     *
     * @return string
     */
    public function getDebugBarHead()
    {
        $renderer = $this->debugbar->getJavascriptRenderer();
        $renderer->setBaseUrl(cPath('/vendor/maximebf/debugbar/src/DebugBar/Resources'));
        return $renderer->renderHead();
    }

    /**
     * Get debug bar <body> section
     *
     * @return string
     */
    public function getDebugBarBody()
    {
        $renderer = $this->debugbar->getJavascriptRenderer();
        return $renderer->render();
    }

}