<?php
/**
 * This file contains the Image class
 */

namespace Charm\Storage;

use Charm\Vivid\Elements\UploadedFile;
use claviska\SimpleImage;

/**
 * Class Image
 *
 * Providing easy image manipulation
 *
 * @package Charm\Storage
 */
class Image extends SimpleImage
{
    /**
     * Loads an image from a file.
     *
     * @param string|UploadedFile $file The image file to load.
     *
     * @return SimpleImage
     * @throws \Exception Thrown if file or image data is invalid.
     */
    public function fromFile($file)
    {
        if (is_string($file)) {
            // Got path string
            return parent::fromFile($file);
        } else if ($file instanceof UploadedFile) {
            // Got uploaded file
            return parent::fromFile($file->getTempName());
        }

        throw new \Exception("File not found: $file", self::ERR_FILE_NOT_FOUND);
    }

}