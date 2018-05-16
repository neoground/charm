<?php
/**
 * This file contains the output interface
 */

namespace Charm\Vivid\Kernel\Interfaces;

/**
 * Interface OutputInterface
 *
 * @package Charm\Vivid\Kernel\Interfaces
 */
interface OutputInterface
{
    /**
     * Output factory
     *
     * @param mixed $val name of view, file or something different
     *
     * @return self
     */
    public static function make($val);

    /**
     * Build the final output which will be sent to the browser
     *
     * @return string
     */
    public function render();

}