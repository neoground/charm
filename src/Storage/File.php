<?php
/**
 * This file contains the File class
 */

namespace Charm\Storage;

use Symfony\Component\Process\Process;

/**
 * Class File
 *
 * Providing file related methods
 *
 * @package Charm\Storage
 */
class File
{
    /**
     * Get the SHA256 checksum of a file
     *
     * @param string $file absolute path to the file
     *
     * @return string returns the 64 characters long SHA256 checksum or "0" on failure
     */
    public static function getSha256Checksum(string $file): string
    {
        $p = new Process(['sha256sum', $file]);
        $p->run();
        if ($p->isSuccessful()) {
            $result = trim($p->getOutput());
            $parts = explode('  ', $result);
            $sum = trim($parts[0]);
            if (strlen($sum) == 64) {
                return $sum;
            }
        }

        return "0";
    }

    /**
     * Get the MD5 checksum of a file
     *
     * @param string $file absolute path to the file
     *
     * @return string returns the 32 characters long MD5 checksum or "0" on failure
     */
    public static function getMd5Checksum(string $file): string
    {
        $md5 = md5_file($file);
        if (!$md5) {
            return "0";
        }
        return $md5;
    }

}