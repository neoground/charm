<?php
/**
 * This file contains the Storage class
 */

namespace Charm\Storage;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;

/**
 * Class Storage
 *
 * Module binding to Charm kernel
 *
 * @package Charm\Storage
 */
class Storage extends Module implements ModuleInterface
{
    /** @var array filesystem instances */
    protected array $filesystems = [];

    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // App's most important dirs
        $this->filesystems['data'] = $this->addLocalFilesystem('data', $this->getDataPath());
        $this->filesystems['var'] = $this->addLocalFilesystem('var', $this->getVarPath());
    }

    /**
     * After init is complete add the user specific file systems
     *
     * This method is called by the post init hook of charm.
     */
    public function postInit()
    {
        $filesystems = C::Config()->get('main:storages');

        if (is_array($filesystems)) {
            foreach ($filesystems as $name => $data) {

                if (is_array($data) && array_key_exists('type', $data)) {

                    switch ($data['type']) {
                        case 'local':
                            if (array_key_exists('path', $data)) {
                                $this->addLocalFilesystem($name, $data['path']);
                            }
                            break;
                        case 'sftp':
                            // All values provided by $data. Only strip type (only used by us)
                            $config = $data;
                            unset($config['type']);

                            $this->addSftpFilesystem($name, $config);
                            break;
                        case 's3':
                            // TODO Add S3 storage options
                            break;
                    }

                }

            }
        }
    }

    /**
     * Add a new local filesystem and return it
     *
     * @param string $name name of filesystem
     * @param string $path path to dir (absolute "/foo/bar" or relative to base dir "data/foobar")
     *
     * @return Filesystem
     */
    public function addLocalFilesystem(string $name, string $path): Filesystem
    {
        if ($path[0] != DS) {
            // Prepend base path because we got a relative path
            $path = $this->getBasePath() . DS . $path;
        }

        return $this->addFilesystemByAdapter($name, new LocalFilesystemAdapter($path));
    }

    /**
     * Add a SFTP filesystem
     *
     * @param string $name   name of filesystem
     * @param array  $config config values (see SftpConnectionProvider)
     *
     * @return Filesystem
     */
    public function addSftpFilesystem(string $name, array $config): Filesystem
    {
        return $this->addFilesystemByAdapter($name, new SftpAdapter(
            new SftpConnectionProvider(
                C::Arrays()->get($config, 'host', 'localhost'),
                C::Arrays()->get($config, 'username', ''),
                C::Arrays()->get($config, 'password', ''),
                C::Arrays()->get($config, 'port', 22),
                C::Arrays()->get($config, 'use_agent', false),
                C::Arrays()->get($config, 'timeout', 10),
            ),
            C::Arrays()->get($config, 'path', '/')
        ));
    }

    /**
     * Add a filesystem by adapter
     *
     * This is useful to add custom storages which are already
     * initialized by the adapter.
     *
     * @param string            $name    storage name
     * @param FilesystemAdapter $adapter the adapter
     *
     * @return Filesystem
     */
    public function addFilesystemByAdapter(string $name, FilesystemAdapter $adapter): Filesystem
    {
        $this->filesystems[$name] = new Filesystem($adapter);
        return $this->filesystems[$name];
    }

    /**
     * Get a filesystem
     *
     * @param string $name name of filesystem
     *
     * @return bool|Filesystem
     */
    public function get($name = 'data')
    {
        return C::Arrays()->get($this->filesystems, $name);
    }

    /**
     * Get the absolute path to the base directory
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return cPath('/');
    }

    /**
     * Get the absolute path to the log directory
     *
     * @return string
     */
    public function getLogPath(): string
    {
        return cPath('/var/logs');
    }

    /**
     * Get the absolute path to the var directory
     *
     * @return string
     */
    public function getVarPath(): string
    {
        return cPath('/var');
    }

    /**
     * Get the absolute path to the cache directors
     *
     * @return string
     */
    public function getCachePath(): string
    {
        return cPath('/var/cache');
    }

    /**
     * Get the absolute path to the app directory
     *
     * @return string
     */
    public function getAppPath(): string
    {
        return $this->getModulePath('App');
    }

    /**
     * Get the absolute path to the module base directory
     *
     * @param string $module name of module
     *
     * @return string
     */
    public function getModulePath($module): string
    {
        return C::get($module)->getBaseDirectory();
    }

    /**
     * Get the absolute path to the assets directory
     *
     * @return string
     */
    public function getAssetsPath(): string
    {
        return cPath('/assets');
    }

    /**
     * Get the absolute path to the data directory
     *
     * @return string
     */
    public function getDataPath(): string
    {
        return cPath('/data');
    }

    /**
     * Format absolute path of file to URL of file
     *
     * @param string $path the absolute path
     *
     * @return string
     */
    public function pathToUrl(string $path): string
    {
        return str_replace(cPath('/'), C::Router()->getBaseUrl(), $path);
    }

    /**
     * Format absolute URL of file to path of file
     *
     * @param string $url the full URL
     *
     * @return string
     */
    public function urlToPath(string $url): string
    {
        return str_replace(C::Router()->getBaseUrl(), cPath('/'), $url);
    }

    /**
     * Scan a local dir for files and directories
     *
     * Will exclude dotfiles
     *
     * @param string $dir           absolute path to directory
     * @param int    $sorting_order optional sorting order. Default: ascending. Set to non-zero for descending
     *
     * @return array returns an array with all files in the dir (or an empty array if there are none)
     */
    public function scanDir(string $dir, int $sorting_order = 0): array
    {
        // Check directory existence and validity
        if (!is_dir($dir)) {
            return [];
        }

        // Use scandir for raw directory scanning
        $entries = scandir($dir, $sorting_order === 0 ? SCANDIR_SORT_ASCENDING : SCANDIR_SORT_DESCENDING);

        // Return only valid entries, excluding dotfiles
        return $entries !== false ? array_diff($entries, ['.', '..']) : [];
    }

    /**
     * Scan a local dir for directories only
     *
     * Will exclude dotfiles
     *
     * @param string $dir           absolute path to directory
     * @param int    $sorting_order optional sorting order. Default: ascending. Set to non-zero for descending
     *
     * @return array returns an array with all directories in the dir (or an empty array if there are none)
     */
    public function scanDirForDirectories(string $dir, int $sorting_order = 0): array
    {
        // Ensure no trailing slash
        $dir = rtrim($dir, '/');

        // Scan directory and filter for directories only
        $entries = $this->scanDir($dir, $sorting_order);
        return array_filter($entries, fn($entry) => is_dir("$dir/" . $entry));
    }

    /**
     * Scan a local dir for files only
     *
     * Will exclude dotfiles
     *
     * @param string $dir           absolute path to directory
     * @param int    $sorting_order optional sorting order. Default: ascending. Set to non-zero for descending
     *
     * @return array returns an array with all files in the dir (or an empty array if there are none)
     */
    public function scanDirForFiles(string $dir, int $sorting_order = 0): array
    {
        // Ensure no trailing slash
        $dir = rtrim($dir, '/');

        // Scan directory and filter for files only
        $entries = $this->scanDir($dir, $sorting_order);
        return array_filter($entries, fn($entry) => is_file("$dir/" . $entry));
    }

    /**
     * Create all directories in this path if they don't exist
     *
     * @param int $mode chmod value (default: 0777)
     *
     * @return bool  true if created or already existing, false on failure
     */
    public function createDirectoriesIfNotExisting(string $path, int $mode = 0777): bool
    {
        return file_exists($path) || mkdir($path, $mode, true);
    }

    /**
     * Delete a file if it exists
     *
     * @param string $file The path to the file
     *
     * @return bool Returns true on success or false on failure
     */
    public function deleteFileIfExists(string $file): bool
    {
        return (file_exists($file) && unlink($file));
    }

    /**
     * Delete a file if it exists
     *
     * @param string $file The path to the file
     *
     * @return bool Returns true on success or false on failure
     */
    public function deleteFile(string $file): bool
    {
        return $this->deleteFileIfExists($file);
    }

    /**
     * Delete a directory (and its content) if it exists
     *
     * @param string $path                The path to the directory
     * @param bool   $delete_files_in_dir Also delete files in the dir? Default: true
     *
     * @return bool
     */
    public function deleteDirectory(string $path, bool $delete_files_in_dir = true): bool
    {
        if (file_exists($path)) {
            if ($delete_files_in_dir) {
                array_map('unlink', glob("$path/*.*"));
            }

            return rmdir($path);
        }

        return false;
    }

    /**
     * Delete all files in a directory (if it exists), but not the directory itself
     *
     * @param string $path The path to the directory
     *
     * @return bool
     */
    public function deleteFilesInDirectory(string $path): bool
    {
        if (file_exists($path)) {
            array_map('unlink', glob("$path/*.*"));
            return true;
        }

        return false;
    }

    /**
     * Get a random filename without extension
     *
     * @param int $length The wanted length (1-25). Recommended default and maximum: 25
     *
     * @return string
     */
    public function getRandomFilename(int $length = 25): string
    {
        if($length === 25) {
            return str_replace('.', '-' . rand(11, 99), uniqid('', true));
        }

        return substr(str_replace('.', rand(11, 99), uniqid('', true)), 0, $length);
    }

}