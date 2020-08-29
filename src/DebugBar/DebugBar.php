<?php
/**
 * This file contains the DebugBar module class
 */

namespace Charm\DebugBar;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
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
        if(C::Config()->get('main:debug.debugmode', false)) {
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
        if(!C::Config()->get('main:debug.show_debugbar', false)
            || !class_exists("DebugBar\\StandardDebugBar")
        ) {
            return false;
        }

        $this->debugBar = new StandardDebugBar();

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
            && C::Config()->get('main:debug.show_debugbar', false)) {
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
        if(!C::Config()->get('main:debug.debugmode', false)
            || !C::Config()->get('main:debug.show_debugbar', false)
        ) {
            return '';
        }

        $r = $this->getRenderer();

        // Add twig template name
        $tpl_name = C::AppStorage()->get('View', 'template_name', false);
        if($tpl_name) {
            $r->addControl('twig_template', [
                "icon" => "eye",
                "tooltip" => "Twig Template",
                "default" => "[]",
                "title" => $tpl_name
            ]);
        }

        // Add current user
        if(C::has('Guard')) {
            if(!C::Guard()->isLoggedIn()) {
                $r->addControl('twig_template', [
                    "icon" => "user",
                    "tooltip" => "Not Logged In",
                    "title" => ""
                ]);
            } else {
                $r->addControl('twig_template', [
                    "icon" => "user",
                    "tooltip" => "Logged in as: " . C::Guard()->getUser()->getDisplayName(),
                    "title" => C::Guard()->getUserId()
                ]);
            }
        }

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
        if(!C::Config()->get('main:debug.debugmode', false)
            || !C::Config()->get('main:debug.show_debugbar', false)
        ) {
            return '';
        }

        $r = $this->getRenderer();
        return $r->render();
    }
}