<?php
/**
 * This file contains the ModuleNotFoundException exception class
 */

namespace Charm\Vivid\Exceptions;

/**
 * Class ModuleNotFoundException
 */
class ModuleNotFoundException extends \Exception
{
    protected $message = 'The wanted kernel module could not be found';
    protected $code = 3001;
}