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

    /**
     * Create a thumbnail for an image
     *
     * @param string $src path to source
     * @param string $dest path where thumbnail should be stored
     * @param int    $width wanted with of thumbnail in px. Default: 600
     * @param string $mime mime of thumbnail. Default: image/jpeg
     * @param int    $quality quality of thumbnail 0-100. Default: 80
     *
     * @return bool
     */
    public static function createThumbnail(string $src,
                                           string $dest,
                                           int $width = 600,
                                           string $mime = "image/jpeg",
                                           int $quality = 80) : bool
    {
        try {
            $tn = new SimpleImage();
            $tn->fromFile($src)
                ->bestFit($width, $width)
                ->thumbnail($width, $width)
                ->toFile($dest, $mime, $quality);

            return true;
        } catch(\Exception $e) {

        }
        return false;
    }

}