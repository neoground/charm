<?php
/**
 * This file contains the PressController class
 */

namespace Charm\DebugBar\Controllers;

use Charm\Vivid\C;
use Charm\Vivid\Controller;
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
        $openHandler = new OpenHandler(C::DebugBar()->getInstance());
        $openHandler->handle();
        C::shutdown();
    }

}