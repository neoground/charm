<?php
/**
 * This file contains the Redirect output class
 */

namespace Charm\Vivid\Kernel\Output;

use Charm\Vivid\C;
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

        if(!empty($val)) {
            $x->withDestination($val, $args);
        }

        return $x;
    }

    /**
     * Build the final output which will be sent to the browser
     *
     * @return string
     */
    public function render()
    {
        // Fire event
        C::Event()->fire('Redirect', 'renderStart');

        // Set current page as last for easier redirecting
        C::Session()->set('charm_last_page', C::Router()->getCurrentUrl());

        // Optinally set message / custom value
        if (!empty($this->message)) {
            C::Session()->set('charm_message', $this->message);
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
        if(str_contains($val, '://')) {
            // Got URL
            $this->destination = $val;
        } else {
            // Got route
            $this->destination = C::Router()->buildUrl($val, $args);
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
     * @return self
     */
    public static function back()
    {
        if (array_key_exists('HTTP_REFERER', $_SERVER)) {
            $back = $_SERVER['HTTP_REFERER'];
        } else {
            // Fallback if referer is not set
            $back = C::Router()->getBaseUrl();
        }

        return self::make($back);
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