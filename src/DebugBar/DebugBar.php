<?php
/**
 * This file contains the Module class
 */

namespace Charm\DebugBar;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
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
        // Get DebugBar instance
        $this->debugBar = Charm::Debug()->getDebugBar();
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
        if(!Charm::Config()->get('main:debug.debugmode', false)) {
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
        if(!Charm::Config()->get('main:debug.debugmode', false)) {
            return '';
        }

        $r = $this->getRenderer();
        return $r->render();
    }
}