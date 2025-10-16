<?php
/**
 * This file contains the PressController class
 */

namespace Charm\DebugBar\Controllers;

use Charm\Vivid\C;
use Charm\Vivid\Controller;
use Charm\Vivid\Kernel\Output\View;
use DebugBar\OpenHandler;

/**
 * Class DebugbarController
 *
 * @package Charm\DebugBar\Controllers
 */
class DebugbarController extends Controller
{
    /**
     * DebugBar open handler
     *
     * @see http://phpdebugbar.com/docs/openhandler.html
     */
    public function getHandler()
    {
        if (C::Config()->get('main:debug.show_debugbar', false) && C::Config()->inDebugMode()) {
            $instance = C::DebugBar()->getInstance();
            if ($instance) {
                $openHandler = new OpenHandler($instance);
                $openHandler->handle();
                C::shutdown();
            }
        }

        return View::makeError('NotFound', 404);
    }

}