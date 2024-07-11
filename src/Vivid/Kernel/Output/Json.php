<?php
/**
 * This file contains the JSON output class
 */

namespace Charm\Vivid\Kernel\Output;

use Charm\Cache\CacheEntry;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\HttpCodes;
use Charm\Vivid\Kernel\Interfaces\OutputInterface;

/**
 * Class Json
 *
 * Creating a JSON output
 *
 * @package Charm\Vivid\Kernel\Output
 */
class Json implements OutputInterface, HttpCodes
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
     * @param array $val        content to output as json
     * @param int   $statuscode (opt.) http status code (default: 200)
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
            'status_on_body' => false,
        ];

        return $x;
    }

    /**
     * Create an error message to return
     *
     * @param string $message    the error message
     * @param int    $statuscode (opt.) the status code (default: 500)
     *
     * @return self
     */
    public static function makeErrorMessage($message, $statuscode = 500)
    {
        return self::make([
            "message" => $message,
        ], $statuscode)
            ->withStatusOnBody();
    }

    /**
     * Add pagination to JSON output
     *
     * This will move every content provided as $data to a 'data' key in the
     * returned JSON.
     *
     * @param int   $total       total entities
     * @param int   $per_page    entities per page
     * @param array $custom_data (opt.) custom data to add to pagination array
     *
     * @return $this
     */
    public function withPagination($total, $per_page, $custom_data = [])
    {
        $get = $_GET;
        $page = (int)C::Request()->get('page', 1);

        $total_pages = ceil($total / $per_page);

        $prev_page_url = null;
        $next_page_url = null;

        $protocol = C::Request()->isHttpsRequest() ? 'https://' : 'http://';

        if ($page > 1) {
            // We have prev page!
            $get['page'] = $page - 1;
            $prev_page_url = $protocol . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($get);
        }

        if ($page < $total_pages) {
            // We have next page!
            $get['page'] = $page + 1;
            $next_page_url = $protocol . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($get);
        }

        $arr = [
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'last_page' => $total_pages,
            'next_page_url' => $next_page_url,
            'prev_page_url' => $prev_page_url,
            'data' => $this->data,
        ];

        if (!empty($custom_data)) {
            $arr['custom_data'] = $custom_data;
        }

        $this->data = $arr;

        return $this;
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
     * @param string $key   the key
     * @param mixed  $value the value
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
        // Fire event
        C::Event()->fire('Json', 'renderStart');

        // Set content type
        header('Content-Type: application/json');

        // Set status code
        http_response_code($this->statuscode);

        // Status on body?
        if (isset($this->settings['status_on_body']) && $this->settings['status_on_body']) {
            $this->data = ["status" => $this->statuscode] + $this->data;
        }

        // Pretty output?
        if (C::Config()->get('main:output.json.pretty', true)) {
            return json_encode($this->data, JSON_PRETTY_PRINT);
        }

        return json_encode($this->data);
    }

    /**
     * Get the content array
     *
     * @return array
     */
    public function getContent()
    {
        return $this->data;
    }

    /**
     * Build the JSON output with caching support.
     *
     * Caching is only used if Cache module is available and enabled
     * (no debug mode or if you're in debug mode and have main:debug.cache_enabled set).
     *
     * @param callable $callback   The callback function to generate the JSON output, must return an array
     * @param string   $key_prefix The cache key or optional prefix. Default is "json_#".
     *                             Appended will be a SHA256 hash of all request values if $key_prefix ends with "_#".
     * @param array    $tags       The tags to associate with the cache entry.
     * @param int      $minutes    The number of minutes to store the cache entry. Default is 1440 (24 hours).
     *
     * @return self The JSON object.
     */
    public static function makeWithCache(callable $callback,
                                         string   $key_prefix = 'json_#',
                                         array    $tags = [],
                                         int      $minutes = 1440)
    {
        if (C::has('Cache') &&
            !(C::Config()->inDebugMode() && !C::Config()->get('main:debug.cache_enabled'))) {
            // Find and return from cache
            $req = hash('sha256', json_encode(C::Request()->getAll()));
            $key = str_replace('_#', '_' . $req, $key_prefix);

            if (C::Cache()->has($key)) {
                return Json::make(C::Cache()->get($key));
            }

            // Store in cache, delete with filtered paginated data cache
            $ce = new CacheEntry($key);
            $ce->setValue($callback());
            if (count($tags) > 0) {
                $ce->setTags($tags);
            }
            C::Cache()->setEntry($ce, $minutes);

            return Json::make($callback());
        }

        return self::make($callback());
    }

}