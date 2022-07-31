<?php
/**
 * This file contains the UploadedFile class.
 */

namespace Charm\Vivid\Elements;

use Charm\Vivid\Exceptions\FileSystemException;


/**
 * Class UploadedFile
 *
 * A single uploaded file
 *
 * @package Charm\Vivid\Elements
 */
class UploadedFile
{
    /** @var array  the uploaded file array */
    protected $file;

    protected int $file_size = 0;
    protected string $file_tmp_name = '';
    protected string $file_mime = '';
    protected string $file_name = '';
    protected string $file_extension = '';
    protected string $file_content = '';

    /**
     * Create instance by uploaded file ($_FILES)
     *
     * @param array $file $_FILES['name'] array
     *
     * @return static
     */
    public static function fromFile(array $file) : self
    {
        $x = new self();

        $path_info = pathinfo($file['name']);

        $x->file_extension = $path_info['extension'];
        $x->file_name = $path_info['filename'];

        if(array_key_exists('size', $file)) {
            $x->file_size = $file['size'];
        }

        if(array_key_exists('type', $file)) {
            $x->file_mime = $file['type'];
        }

        $x->file_tmp_name = $file['tmp_name'];

        return $x;
    }

    /**
     * Create instance by base64 file string
     *
     * @param string $base64
     *
     * @return false|static returns false if base64 is invalid
     */
    public static function fromBase64(string $base64) : self|false
    {
        $x = new self();

        $base64_parts = explode(";", $base64);
        $mime = str_replace('data:', '', $base64_parts[0]);

        $x->file_content = base64_decode(str_replace('base64,', '', $base64_parts[1]));
        $x->file_mime = $mime;

        if($x->file_content === false) {
            return false;
        }

        return $x;
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtension() : string
    {
        return $this->file_extension;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename() : string
    {
        return $this->file_name;
    }

    /**
     * Get file size in bytes
     *
     * @return int
     */
    public function getSize() : int
    {
        return $this->file_size;
    }

    /**
     * Get mime type of this file
     *
     * @return string
     */
    public function getMime(): string
    {
        return $this->file_mime;
    }

    /**
     * Get absolute path to temp file
     *
     * @return string
     */
    public function getTempName(): string
    {
        return $this->file_tmp_name;
    }

    /**
     * Save uploaded file as
     *
     * This will override the $dest file if it exists.
     *
     * @param string $dest     the absolute path to the destination with filename
     * @param bool   $override override file if existing? Default: true
     *
     * @throws FileSystemException
     */
    public function saveAs(string $dest, bool $override = true): void
    {
        if (file_exists($dest) && $override) {
            unlink($dest);
        }

        if(!empty($this->file_content)) {
            file_put_contents($dest, $this->file_content);
        } else {
            if (!move_uploaded_file($this->getTempName(), $dest)) {
                // Was not successful. Show error
                throw new FileSystemException("File upload failed");
            }
        }
    }
}