<?php
/**
 * This file contains the init class for the requests.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Charm;
use Charm\Vivid\Elements\UploadedFile;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Request
 *
 * Request module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Request implements ModuleInterface
{
    /** @var array All request variables */
    protected $vars;

    /** @var array All uploaded files */
    protected $files;

    /**
     * Load the module
     */
    public function loadModule()
    {
        // Add all request variables
        $this->vars = $_REQUEST;

        // Add all uploads
        $this->files = $_FILES;

        // JSON content to add?
        if (array_key_exists('CONTENT_TYPE', $_SERVER)
            && strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false
        ) {
            $postdata = file_get_contents("php://input");
            $data_json = json_decode($postdata, true);

            if (is_array($data_json)) {
                foreach ($data_json as $k => $v) {
                    $this->vars[$k] = $v;
                }
            }
        }
    }

    /**
     * Get a request value
     *
     * @param $key     string     the wanted key, arrays separated by .
     * @param $default null|mixed (optional) default parameter
     *
     * @return null|string|array
     */
    public function get($key, $default = null)
    {
        return Charm::Arrays()->get($this->vars, $key, $default);
    }

    /**
     * Check if the request contains this key
     *
     * @param string $key the key
     *
     * @return bool
     */
    public function has($key)
    {
        return Charm::Arrays()->has($this->vars, $key);
    }

    /**
     * Get all passed values as array
     *
     * @return array
     */
    public function getAll()
    {
        return $this->vars;
    }

    /**
     * Get all POST data
     *
     * @return array
     */
    public function getAllPost()
    {
        return $_POST;
    }

    /**
     * Get all GET data
     *
     * @return array
     */
    public function getAllGet()
    {
        return $_GET;
    }

    /**
     * Get specific header value
     *
     * @param string $key header key
     * @param null|mixed $default (optional) default parameter
     *
     * @return null|string
     */
    public function getHeader($key, $default = null)
    {
        return Charm::Arrays()->get(apache_request_headers(), $key, $default);
    }

    /**
     * Check if a file was uploaded successfully
     *
     * @param string $name name field of uploaded file
     *
     * @return bool
     */
    public function gotUpload($name)
    {
        return array_key_exists($name, $this->files)
            && is_array($this->files[$name])
            && file_exists($this->files[$name]['tmp_name'])
            && is_uploaded_file($this->files[$name]['tmp_name']);
    }

    /**
     * Get uploaded file
     *
     * @param string $name name field of uploaded file
     *
     * @return bool|UploadedFile  Returns the uploaded file or false if not found
     */
    public function getFile($name)
    {
        if (!$this->gotUpload($name)) {
            return false;
        }

        return new UploadedFile($this->files[$name]);
    }


}