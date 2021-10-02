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
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PhpseclibV2\SftpAdapter;
use League\Flysystem\PhpseclibV2\SftpConnectionProvider;

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
    protected $filesystems = [];

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
                        case 'ftp':
                            // All values provided by $data. Only strip type (only used by us)
                            $config = $data;
                            unset($config['type']);

                            $this->addFtpFilesystem($name, $config);
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
     * Add a FTP filesystem
     *
     * @param string $name   name of filesystem
     * @param array  $config config values (see FtpConnectionOptions)
     *
     * @return Filesystem
     */
    public function addFtpFilesystem(string $name, array $config): Filesystem
    {
        return $this->addFilesystemByAdapter($name, new FtpAdapter(
            FtpConnectionOptions::fromArray($config)
        ));
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

    public function addS3Filesystem($name, $config)
    {
        // TODO Add support for S3 storage
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
     * @param string $dir absolute path to directory
     *
     * @return array|false returns false on error
     */
    public function scanDir($dir)
    {
        if(!file_exists($dir) || !is_dir($dir)) {
            return false;
        }
        return array_diff(scandir($dir), ['.', '..']);
    }

    /**
     * Scan a local dir for directories only
     *
     * Will exclude dotfiles
     *
     * @param string $dir absolute path to directory
     *
     * @return array|false returns false on error
     */
    public function scanDirForDirectories($dir)
    {
        $ret = [];
        foreach($this->scanDir($dir) as $file) {
            if(is_dir($dir . DS . $file)) {
                $ret[] = $file;
            }
        }
        return $ret;
    }

    /**
     * Scan a local dir for files only
     *
     * Will exclude dotfiles
     *
     * @param string $dir absolute path to directory
     *
     * @return array|false returns false on error
     */
    public function scanDirForFiles($dir)
    {
        $ret = [];
        foreach($this->scanDir($dir) as $file) {
            if(is_file($dir . DS . $file)) {
                $ret[] = $file;
            }
        }
        return $ret;
    }

    /**
     * Create all directories in this path if they don't exist
     *
     * @param int $mode chmod value (default: 0777)
     *
     * @return bool  true if created or already existing, false on failure
     */
    public function createDirectoriesIfNotExisting(string $path, int $mode = 0777) : bool
    {
        return file_exists($path) || mkdir($path, $mode, true);
    }

    /**
     * Delete a file if it exists
     *
     * @param string $file path to file
     *
     * @return bool true on deletion false on error or if not found
     */
    public function deleteFileIfExists(string $file) : bool
    {
        return (file_exists($file) && unlink($file));
    }

}