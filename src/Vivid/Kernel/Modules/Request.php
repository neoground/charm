<?php
/**
 * This file contains the init class for the requests.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Storage\Image;
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
        $this->vars = array_merge($this->vars, (array)$putdata);
    }

    /**
     * Get a request value
     *
     * @param $key      string        the wanted key, arrays separated by .
     * @param $default  null|mixed    (optional) default parameter
     * @param $sanitize bool|callable (optional) sanitize request value (will strip tags if set to true or use your own
     *                  sanitizing function)
     *
     * @return null|string|array
     */
    public function get($key, $default = null, $sanitize = false)
    {
        if ($sanitize !== false) {
            if (is_callable($sanitize)) {
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
    public function has($key): bool
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
     * @param mixed  $value
     *
     * @return true
     */
    public function set($key, $value): bool
    {
        $this->vars[$key] = $value;
        return true;
    }

    /**
     * Set a request value if it's not set yet or empty
     *
     * This can be used for custom overriding or
     * for internal calling of other controller methods and so on.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool true if set, false if existing
     */
    public function setIfEmpty($key, $value): bool
    {
        if (!$this->has($key) || empty($this->get($key))) {
            $this->set($key, $value);
            return true;
        }
        return false;
    }

    /**
     * Get multiple fields at once in an array
     *
     * @param array $keys the keys to get
     *
     * @return array keys are the $keys, values are the found values
     */
    public function getMultiple(array $keys): array
    {
        $data = [];

        foreach ($keys as $key) {
            $val = $this->get($key);

            if ($val !== 0 && empty($val)) {
                $val = null;
            }

            $data[$key] = $val;
        }

        return $data;
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
     * @param string     $key     header key
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
     * Note: This does only work for classic file uploads, not for base64 strings etc.
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
     * Check if a file was uploaded successfully
     *
     * Just a wrapper for $this->gotUpload()
     *
     * @param string $name name field of uploaded file
     *
     * @return bool
     */
    public function hasFile($name)
    {
        return $this->gotUpload($name);
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
            // Check for base64 string
            $req = $this->get($name);
            if (!empty($req)) {
                $upload = UploadedFile::fromBase64($req);
                if ($upload !== false) {
                    return $upload;
                }
            }

            return false;
        }

        return UploadedFile::fromFile($this->files[$name]);
    }

    /**
     * Save an uploaded file
     *
     * Multiple files must be handled manually, also due to filenames etc.
     *
     * @param string $name        name of field of the uploaded file
     * @param string $destination absolute path where the file should be stored
     * @param bool   $override    override file if existing? Default: true
     *
     * @return bool true if saved false on error
     */
    public function saveFile($name, $destination, $override = true): bool
    {
        $file = $this->getFile($name);
        if ($file) {
            $dir = dirname($destination);
            C::Storage()->createDirectoriesIfNotExisting($dir);

            try {
                $file->saveAs($destination, $override);
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Save and resize / compress an image
     *
     * @param string $name        name of field of the uploaded file
     * @param string $destination absolute path where the file should be stored
     * @param bool   $override    override file if existing? Default: true
     * @param int    $width       width of image to resize to. Set to 0 for no resizing
     * @param string $mime        save file as this mime. leave empty to use the source's mime
     * @param int    $quality
     *
     * @return bool
     */
    public function saveAndResizeImage(string $name,
                                       string $destination,
                                       bool   $override = true,
                                       int    $width = 1920,
                                       string $mime = "image/jpeg",
                                       int    $quality = 90): bool
    {

        if ($this->saveFile($name, $destination, $override)) {

            if (empty($mime)) {
                $mime = mime_content_type($destination);
            }

            try {
                $img = new Image();
                $img->fromFile($destination)
                    ->autoOrient();

                if ($width > 0) {
                    $img = $img->resize($width);
                }

                $img->toFile($destination, $mime, $quality);
                return true;
            } catch (\Exception $e) {
                return false;
            }

        }

        return false;
    }

    /**
     * Save an image as a thumbnail (cropped)
     *
     * @param string $name        name of field of the uploaded file
     * @param string $destination absolute path where the file should be stored
     * @param bool   $override    override file if existing? Default: true
     * @param int    $width       width of thumbnail
     * @param int    $height      height of thumbnail. Set to 0 (default) to use the width (square)
     * @param string $mime        save file as this mime. leave empty to use the source's mime
     * @param int    $quality
     *
     * @return bool
     */
    public function saveImageAsThumbnail(string $name,
                                         string $destination,
                                         bool   $override = true,
                                         int    $width = 600,
                                         int    $height = 0,
                                         string $mime = "image/jpeg",
                                         int    $quality = 80): bool
    {
        if ($this->saveFile($name, $destination, $override)) {

            if (empty($mime)) {
                $mime = mime_content_type($destination);
            }

            if (empty($height)) {
                $height = $width;
            }

            try {
                $img = new Image();
                $img->fromFile($destination)
                    ->autoOrient()
                    ->bestFit($width, $height)
                    ->thumbnail($width, $height)
                    ->toFile($destination, $mime, $quality);
                return true;
            } catch (\Exception $e) {
                return false;
            }

        }

        return false;
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

        // Looping through all files and add existing ones (ignore empty array elements)
        for ($i = 0; $i < $total; $i++) {
            if (!empty($this->files[$name]['tmp_name'][$i])) {
                $arr[] = UploadedFile::fromFile([
                    'name' => $this->files[$name]['name'][$i],
                    'type' => $this->files[$name]['type'][$i],
                    'size' => $this->files[$name]['size'][$i],
                    'tmp_name' => $this->files[$name]['tmp_name'][$i],
                    'error' => $this->files[$name]['error'][$i],
                ]);
            }
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
        } else if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * Is the current request via HTTPS?
     *
     * @return bool
     */
    public function isHttpsRequest(): bool
    {
        return isset($_SERVER['HTTPS'])
            || (array_key_exists('HTTP_X_FORWARDED_PROTO', $_SERVER) && str_contains($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https'))
            || (array_key_exists('HTTP_CF_VISITOR', $_SERVER) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
            || C::Config()->get('main:request.force_https', false);
    }

    /**
     * Does the browser accept this content type?
     *
     * Checks HTTP_ACCEPT header
     *
     * @param string $str the content type
     *
     * @return bool
     */
    public function accepts(string $str): bool
    {
        return array_key_exists('HTTP_ACCEPT', $_SERVER) && str_contains($_SERVER['HTTP_ACCEPT'], $str);
    }

    /**
     * Save all request input values in session
     *
     * @return void
     */
    public function saveAllInSession(): void
    {
        C::Session()->set('charm_request_input', $this->getAll());
    }

    /**
     * Get all request input values which are stored in session
     *
     * @return array|bool the array or false if none was found
     */
    public function getAllFromSession(): array|bool
    {
        return C::Session()->get('charm_request_input', false);
    }

}