<?php
/**
 * This file contains the controller class
 */

namespace Charm\Vivid;

use Charm\Vivid\Kernel\Modules\Request;

/**
 * Class Controller
 *
 * The base controller
 *
 * @package Charm\Vivid
 */
class Controller
{
    /**
     * Logged in user
     *
     * @var \App\Models\User
     */
    protected $user;

    /**
     * The request object
     *
     * @var Request
     */
    protected $request;

    /**
     * Controller constructor
     */
    public function __construct()
    {
        // Add logged in user if guard is enabled
        if(Charm::App()->getConfig('guard_enabled', true)) {
            $this->user = Charm::Guard()->getUser();
        }

        // Add request
        $this->request = Charm::Request();
    }
}