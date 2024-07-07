<?php
/**
 * This file contains the init class for debugging.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Debug
 *
 * Debug module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Debug extends Module implements ModuleInterface
{
    /** @var object the whoops instance */
    protected object $whoops;

    /**
     * Load the module
     */
    public function loadModule()
    {
        // Only init debug modules if we're in debug mode
        if (C::Config()->get('main:debug.debugmode', false)) {
            $this->initWhoops();
        } else {
            // No debug mode -> production!
            if(class_exists("Kint")) {
                \Kint::$enabled_mode = false;
            }
        }

        // Set kint settings if available
        if(class_exists("Kint")) {
            \Kint::$aliases[] = 'ddd';
            \Kint\Renderer\RichRenderer::$folder = false;
        }
    }

    /**
     * Init whoops error page
     *
     * @return bool
     */
    private function initWhoops(): bool
    {
        if (!class_exists("Whoops\\Run")) {
            return false;
        }

        $whoops = new \Whoops\Run;
        $handle = new \Whoops\Handler\PrettyPageHandler;

        $handle->addDataTableCallback('Charm', [self::class, 'getWhoopsMetadata']);

        $root_path = C::Storage()->getBasePath();
        if ($root_path) {
            $handle->setApplicationRootPath($root_path);
        }

        $editor = C::Config()->get('main:debug.editor', false);
        if ($editor) {
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
            $whoops->pushHandler(new \Whoops\Handler\PlainTextHandler());
        } else if (\Whoops\Util\Misc::isAjaxRequest()) {
            $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler);
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
    public static function getWhoopsMetadata(): array
    {
        $route = null;
        if (C::has('Router')) {
            $route = C::Router()->getCurrentRouteData();
        }

        return [
            'framework' => 'Charm',
            'version' => C::VERSION,
            'route' => $route,
        ];
    }

    /**
     * Get the whoops instance
     *
     * @return object|\Whoops\Run
     */
    public function getWhoopsInstance(): object
    {
        return $this->whoops;
    }

}