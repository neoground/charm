<?php
/**
 * This file contains the Module class
 */

namespace Charm\Validator;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Validator
 *
 * Module binding to Charm kernel
 *
 * @package Charm\Validator
 */
class Validator extends Module implements ModuleInterface
{
    /**
     * Load the module
     */
    public function loadModule()
    {
        // Nothing to do here yet.
    }

    /**
     * Validate fields
     *
     * @param array $fields
     *
     * @return array|mixed|true
     */
    public function validate($fields)
    {
        $vi = new ValidationInstance($fields);
        return $vi->check($fields)->validateAll();
    }
}