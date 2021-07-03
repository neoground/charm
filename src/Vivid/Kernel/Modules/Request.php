<?php
/**
 * This file contains the init class for the requests.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Elements\UploadedFile;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Request
 *
 * Request module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Request extends Module implements ModuleInterface
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
                $this->vars = array_merge($this->vars, $data_json);
            }
        }

        // Data from put requests and so on?
        parse_str(file_get_contents("php://input"), $putdata);
        $this->vars = array_merge($this->vars, (array) $putdata);
    }

    /**
     * Get a request value
     *
     * @param $key      string        the wanted key, arrays separated by .
     * @param $default  null|mixed    (optional) default parameter
     * @param $sanitize bool|callable (optional) sanitize request value (will strip tags if set to true or use your own sanitizing function)
     *
     * @return null|string|array
     */
    public function get($key, $default = null, $sanitize = false)
    {
        if($sanitize !== false) {
            if(is_callable($sanitize)) {
                return $sanitize(C::Arrays()->get($this->vars, $key, $default));
            }
            return strip_tags(C::Arrays()->get($this->vars, $key, $default));
        }

        return C::Arrays()->get($this->vars, $key, $default);
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
        return C::Arrays()->has($this->vars, $key);
    }

    /**
     * Set a request value
     *
     * This can be used for custom overriding or
     * for internal calling of other controller methods and so on.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return true
     */
    public function set($key, $value)
    {
        $this->vars[$key] = $value;
        return true;
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
        // Make header keys + wanted key lowercase to prevent problems
        $headers = array_change_key_case(apache_request_headers());
        $key = strtolower($key);

        return C::Arrays()->get($headers, $key, $default);
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

    /**
     * Get multiple uploaded files
     *
     * @param string $name name field of uploaded file
     *
     * @return UploadedFile[]
     */
    public function getFiles($name)
    {
        $arr = [];
        $total = count($this->files[$name]['name']);

        // Looping through all files
        for($i = 0; $i < $total; $i++){
            $arr[] = new UploadedFile([
                'name' => $this->files[$name]['name'][$i],
                'type' => $this->files[$name]['type'][$i],
                'size' => $this->files[$name]['size'][$i],
                'tmp_name' => $this->files[$name]['tmp_name'][$i],
                'error' => $this->files[$name]['error'][$i],
            ]);
        }

        return $arr;
    }

    /**
     * Get ip address of current request
     *
     * @return string
     */
    public function getIpAddress()
    {
        // Cloudflare origin ip support
        $cf_ip = $this->getHeader('CF-Connecting-IP');
        if (!empty($cf_ip)) {
            return $cf_ip;
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }


}