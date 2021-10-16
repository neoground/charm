<?php
/**
 * This file contains the DebugBar module class
 */

namespace Charm\DebugBar;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use DebugBar\Storage\FileStorage;

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

    /** @var bool is debug bar enabled? */
    protected $enabled;

    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        $this->enabled = false;

        // Only init if we're in debug mode
        if(C::Config()->inDebugMode()) {
            $this->initDebugBar();
        }
    }

    private function initDebugBar() : bool
    {
        // No debug bar if disabled or not installed
        if(!C::Config()->get('main:debug.show_debugbar', false)
            || !class_exists("DebugBar\\StandardDebugBar")
        ) {
            $this->enabled = false;
            return false;
        }

        $this->enabled = true;
        $this->debugBar = new StandardDebugBar();

        // Set storage if enabled
        if(C::Config()->get('main:debug.log_debugbar', true)) {
            $this->debugBar->setStorage(new FileStorage(C::Storage()->getCachePath() . DS . 'debugbar'));
        }

        return true;
    }

    public function isEnabled() : bool
    {
        return $this->enabled;
    }

    public function getInstance() : StandardDebugBar|false
    {
        return ($this->isEnabled() ? $this->debugBar : false);
    }

    public function getRenderer() : JavascriptRenderer
    {
        if(!is_object($this->debugBarRenderer) && $this->isEnabled()) {
            $this->debugBarRenderer = $this->debugBar->getJavascriptRenderer();
            $this->debugBarRenderer->setBaseUrl(cBaseUrl() . '/vendor/maximebf/debugbar/src/DebugBar/Resources');
            $this->debugBarRenderer->setOpenHandlerUrl(cBaseUrl() . '/charm/debugbar_handler');
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
        if(!$this->isEnabled()) {
            return '';
        }

        $r = $this->getRenderer();

        // Add twig template name
        $tpl_name = C::AppStorage()->get('View', 'template_name', false);
        if(!empty($tpl_name)) {
            $r->addControl('twig_template', [
                "icon" => "eye",
                "tooltip" => "Twig Template",
                "default" => "[]",
                "title" => $tpl_name
            ]);
        }

        $route_data = C::Router()->getCurrentRouteData();
        if(is_array($route_data)) {
            $r->addControl('current_route', [
                "icon" => "map-marker",
                "tooltip" => $route_data['class'] . '.' .$route_data['method'],
                "default" => "[]",
                "title" => C::Server()->get('REQUEST_METHOD') . ' ' . $route_data['name']
            ]);
        }

        // Add current user
        if(C::has('Guard')) {
            if(!C::Guard()->isLoggedIn()) {
                $r->addControl('current_user', [
                    "icon" => "user",
                    "tooltip" => "Not Logged In",
                    "title" => ""
                ]);
            } else {
                $r->addControl('current_user', [
                    "icon" => "user",
                    "tooltip" => "Logged in as: " . C::Guard()->getUser()->getDisplayName(),
                    "title" => C::Guard()->getUserId()
                ]);
            }
        }

        // Add custom style
        $css_path = C::Storage()->getModulePath('DebugBar') . DS . 'debugbar.css';
        $css_url = C::Storage()->pathToUrl($css_path);

        return $r->renderHead() . "\n" . '<link rel="stylesheet" type="text/css" href="' . $css_url . '">';
    }

    /**
     * Output <body> content
     *
     * @return string
     */
    public function getRenderBar()
    {
        // Only in debug mode!
        if(!$this->isEnabled()) {
            return '';
        }

        $r = $this->getRenderer();
        return $r->render();
    }

    /**
     * Debug multiple vars (outputting to debug bar)
     *
     * Can be used as a replacement for ddd()
     *
     * @param mixed ...$params
     */
    public function debugVar(...$params)
    {
        if($this->isEnabled()) {
            foreach ($params as $param) {
                $this->getInstance()['messages']->debug($param);
            }
        }
    }
}