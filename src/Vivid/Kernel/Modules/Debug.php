<?php
/**
 * This file contains the init class for debugging.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Kint\Renderer\RichRenderer;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
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
    /** @var Run the whoops instance */
    protected Run $whoops;

    /**
     * Load the module
     */
    public function loadModule()
    {
        // Only init debug modules if we're in debug mode
        if(C::Config()->get('main:debug.debugmode', false)) {
            $this->initWhoops();
        } else {
            // No debug mode -> production!
            \Kint::$enabled_mode = false;
        }

        // Set kint settings
        \Kint::$aliases[] = 'ddd';
        RichRenderer::$folder = false;
    }

    /**
     * Init whoops error page
     *
     * @return bool
     */
    private function initWhoops() : bool
    {
        if(!class_exists("Whoops\\Run")) {
            return false;
        }

        $whoops = new Run;
        $handle = new PrettyPageHandler;

        $handle->addDataTableCallback('charm', [self::class, 'getWhoopsMetadata']);

        $root_path = C::Config()->get('main:debug.base_path', false);
        if($root_path !== false) {
            $handle->setApplicationRootPath($root_path);
        }

        $editor = C::Config()->get('main:debug.editor', false);
        if($editor !== false) {
            $handle->setEditor($editor);
        }

        $handle->setPageTitle("Whoops! Charm Error");

        // Add custom style
        $css_path = C::Storage()->getBasePath() . DS . 'vendor' . DS . 'neoground' . DS . 'charm'
            . DS . 'src' . DS . 'Vivid' . DS . 'assets' . DS . 'whoops_custom.css';
        $handle->addResourcePath(dirname($css_path));
        $handle->addCustomCss(basename($css_path));

        // Output depending on CLI / AJAX Request / default view
        if (is_cli()) {
            $whoops->pushHandler(new PlainTextHandler());
        } elseif (Misc::isAjaxRequest()) {
                $whoops->pushHandler(new JsonResponseHandler);
        } else {
            $whoops->pushHandler($handle);
        }

        $whoops->register();
        $this->whoops = $whoops;
        return true;
    }

    /**
     * Get metadata which will be added to "whoops" error page
     *
     * @return array
     */
    public static function getWhoopsMetadata() : array
    {
        $route = null;
        if(C::has('Router')) {
            $route = C::Router()->getCurrentRouteData();
        }

        return [
            'framework' => 'Charm',
            'version' => C::VERSION,
            'route' => $route
        ];
    }

    /**
     * Get the whoops instance
     *
     * @return Run
     */
    public function getWhoopsInstance() : Run
    {
        return $this->whoops;
    }

}