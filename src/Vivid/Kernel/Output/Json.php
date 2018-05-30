<?php
/**
 * This file contains the JSON output class
 */

namespace Charm\Vivid\Kernel\Output;

use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\OutputInterface;

/**
 * Class Json
 *
 * Creating a JSON output
 *
 * @package Charm\Vivid\Kernel\Output
 */
class Json implements OutputInterface
{

    /** @var array data to output as json */
    protected $data = [];

    /** @var int status code */
    protected $statuscode;

    /** @var array  output settings */
    protected $settings = [];

    /**
     * Output factory
     *
     * @param array  $val         content to output as json
     * @param int    $statuscode  (opt.) http status code (default: 200)
     *
     * @return self
     */
    public static function make($val = null, $statuscode = 200)
    {
        $x = new self;
        $x->data = $val;
        $x->statuscode = $statuscode;

        // Default settings
        $x->settings = [
            'status_on_body' => false
        ];

        return $x;
    }

    /**
     * Create an error message to return
     *
     * @param string  $message     the error message
     * @param int     $statuscode  (opt.) the status code (default: 500)
     *
     * @return self
     */
    public static function makeErrorMessage($message, $statuscode = 500)
    {
        return self::make([
            "message" => $message
        ], $statuscode)
            ->withStatusOnBody();
    }

    /**
     * Return status code on body when rendering
     *
     * @return $this
     */
    public function withStatusOnBody()
    {
        $this->settings['status_on_body'] = true;
        return $this;
    }

    /**
     * Add an value to the return data array
     *
     * @param string  $key    the key
     * @param string  $value  the value
     *
     * @return $this
     */
    public function add($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Build the final output which will be sent to the browser
     *
     * @return string
     */
    public function render()
    {
        // Set content type
        header('Content-type: application/json');

        // Status on body?
        if(isset($this->settings['status_on_body']) && $this->settings['status_on_body']) {
            $this->data = ["status" => $this->statuscode] + $this->data;
        }

        // Pretty output?
        if (Charm::Config()->get('main:output.json.pretty', true)) {
            return json_encode($this->data, JSON_PRETTY_PRINT);
        }

        return json_encode($this->data);
    }

}