<?php
/**
 * This file contains the ViewException exception class
 */

namespace Charm\Vivid\Exceptions;

use Throwable;

/**
 * Class ViewException
 *
 * Code inspired by W
 */
class ViewException extends \Exception
{
    protected $code = 3011;

    protected $file = '';

    protected $line = '';

    public function __construct($file, $line, $message = "", $code = 3011, Throwable $previous = null)
    {
        $this->file = $file;
        $this->line = $line;
        parent::__construct($message, $code, $previous);
    }
}