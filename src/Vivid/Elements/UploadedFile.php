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

    /** @var array  the path info */
    protected $path_info;

    /**
     * UploadedFile constructor.
     *
     * @param array  $file  the uploaded file array
     */
    public function __construct($file)
    {
        // Set file
        $this->file = $file;

        // Get path info
        $this->path_info = pathinfo($this->file['name']);
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->path_info['extension'];
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->path_info['filename'];
    }

    /**
     * Save uploaded file as
     *
     * This will override the $dest file if it exists.
     *
     * @param string  $dest      the absolute path to the destination with filename
     *
     * @throws FileSystemException
     */
    public function saveAs($dest)
    {
        if (file_exists($dest)) {
            unlink($dest);
        }

        if (!move_uploaded_file($this->file['tmp_name'], $dest)) {
            // Was not successful. Show error
            throw new FileSystemException("File upload failed");
        }
    }
}