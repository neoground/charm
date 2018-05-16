<?php
/**
 * This file contains the Redirect output class
 */

namespace Charm\Vivid\Kernel\Output;

use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\OutputInterface;

/**
 * Class Json
 *
 * Creating a Redirect output
 *
 * @package Charm\Vivid\Kernel\Output
 */
class Redirect implements OutputInterface
{
    /**
     * The destination URL to redirect to
     *
     * @var string
     */
    protected $destination;

    /**
     * Optional message
     *
     * @var string
     */
    protected $message;

    /**
     * Output factory
     *
     * @param null   $val   (optional) redirect destination
     * @param array  $args  (optional) arguments for route building
     *
     * @return self
     */
    public static function make($val = null, $args = [])
    {
        $x = new self;
        $x->withDestination($val, $args);
        return $x;
    }

    /**
     * Build the final output which will be sent to the browser
     *
     * @return string
     */
    public function render()
    {
        // Optinally set message / custom value
        if (!empty($this->message)) {
            Charm::Session()->set('charm_message', $this->message);
        }

        // Set redirect header
        header("Location: " . $this->destination);

        // No content to return
        return "";
    }

    /**
     * Add the destination route / URL
     *
     * @param string        $val
     * @param array|string  $args  (optional) arguments for route building
     *
     * @return self
     */
    public function withDestination($val, $args = [])
    {
        if(in_string('://', $val)) {
            // Got URL
            $this->destination = $val;
        } else {
            // Got route
            $this->destination = Charm::Router()->buildUrl($val, $args);
        }

        return $this;
    }

    /**
     * Add a session message which can be displayed after redirect
     *
     * @param string $msg
     *
     * @return self
     */
    public function withMessage($msg)
    {
        $this->message = $msg;
        return $this;
    }

    /**
     * Redirect to last page
     *
     */
    public function back()
    {
        // TODO: Implement
    }

    /**
     * Send all submitted form fields to redirect page
     *
     * $_GET / $_POST will be saved in $_SESSION
     */
    public function withFormFields()
    {
        // TODO: Implement
    }

    /**
     * Redirect to a specific route / url
     *
     * @param string         $destination  redirect destination (route / URL)
     * @param array|string   $args         (optional) arguments for route building
     *
     * @return self
     */
    public static function to($destination, $args = [])
    {
        return self::make($destination, $args);
    }

}