<?php
/**
 * This file contains the DebugBar module class
 */

namespace Charm\DebugBar;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;
use Charm\Vivid\Helper\ModuleDescriber;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;

/**
 * Class Module
 *
 * Module binding to Charm kernel
 *
 * @package Charm\Guard
 */
class DebugBar extends Module implements ModuleInterface
{
    /** @var StandardDebugBar the debug bar instance */
    protected $debugBar;

    /** @var JavascriptRenderer the javascript renderer instance */
    protected $debugBarRenderer;

    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // Only init if we're in debug mode
        if(Charm::Config()->get('main:debug.debugmode', false)) {
            $this->initDebugBar();
        }
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

        $this->debugBar = new StandardDebugBar();
        $this->debugBar->addCollector(new TimeDataCollector());
        $this->debugBar->addCollector(new MessagesCollector());
        $this->debugBar->addCollector(new RequestDataCollector());

        return true;
    }

    /**
     * Get debug bar instance
     *
     * @return StandardDebugBar
     */
    public function getInstance()
    {
        return $this->debugBar;
    }

    /**
     * Get debug bar javascript renderer if debug bar is enabled
     *
     * @return JavascriptRenderer
     */
    public function getRenderer()
    {
        if(!is_object($this->debugBarRenderer)
            && Charm::Config()->get('main:debug.show_debugbar', false)) {
            $this->debugBarRenderer = $this->debugBar->getJavascriptRenderer();
            $this->debugBarRenderer->setBaseUrl(cBaseUrl() . '/vendor/maximebf/debugbar/src/DebugBar/Resources');
        }

        return $this->debugBarRenderer;
    }

    /**
     * Output <head> content
     *
     * @return string
     */
    public function getRenderHead()
    {
        // Only in debug mode!
        if(!Charm::Config()->get('main:debug.debugmode', false)
            || !Charm::Config()->get('main:debug.show_debugbar', false)
        ) {
            return '';
        }

        $r = $this->getRenderer();
        return $r->renderHead();
    }

    /**
     * Output <body> content
     *
     * @return string
     */
    public function getRenderBar()
    {
        // Only in debug mode!
        if(!Charm::Config()->get('main:debug.debugmode', false)
            || !Charm::Config()->get('main:debug.show_debugbar', false)
        ) {
            return '';
        }

        $r = $this->getRenderer();
        return $r->render();
    }
}