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
     * @return Image
     * @throws \Exception
     */
    public function fromFile(string|UploadedFile $file): static
    {
        if (is_string($file)) {
            // Got path string
            parent::fromFile($file);
        } else {
            // Object
            parent::fromString($file->getFileContent());
        }

        return $this;
    }

    /**
     * Create a thumbnail for an image
     *
     * @param string $src path to source
     * @param string $dest path where thumbnail should be stored
     * @param int    $width wanted with of thumbnail in px. Default: 600
     * @param int    $height wanted height of thumbnail in px. Default: 600
     * @param string $mime mime of thumbnail. Default: image/jpeg
     * @param int    $quality quality of thumbnail 0-100. Default: 80
     *
     * @return bool
     */
    public static function createThumbnail(string $src,
                                           string $dest,
                                           int $width = 600,
                                           int $height = 600,
                                           string $mime = "image/jpeg",
                                           int $quality = 80) : bool
    {
        try {
            $tn = new SimpleImage();
            $tn->fromFile($src)
                ->bestFit($width, $height)
                ->thumbnail($width, $height)
                ->toFile($dest, $mime, $quality);

            return true;
        } catch(\Exception $e) {

        }
        return false;
    }

}