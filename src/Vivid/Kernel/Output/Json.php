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

        return $x;
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

        // Pretty output?
        if (Charm::Config()->get('main:output.json.pretty', true)) {
            return json_encode($this->data, JSON_PRETTY_PRINT);
        }

        return json_encode($this->data);
    }

}