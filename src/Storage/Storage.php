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
 * TODO: Add support for S3 / FTP / SFTP via Flysystem
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

        if(is_array($filesystems)) {
            foreach($filesystems as $name => $data) {

                if(is_array($data) && array_key_exists('type', $data)) {

                    switch($data['type']) {
                        case 'local':
                            if(array_key_exists('path', $data)) {
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
    public function addLocalFilesystem($name, $path)
    {
        if($path[0] != DS) {
            // Prepend base path because we got a relative path
            $path = $this->getBasePath() . DS . $path;
        }

        return $this->addFilesystemByAdapter($name, new LocalFilesystemAdapter($path));
    }

    /**
     * Add a FTP filesystem
     *
     * @param string $name name of filesystem
     * @param array $config config values (see FtpConnectionOptions)
     *
     * @return Filesystem
     */
    public function addFtpFilesystem($name, $config)
    {
        return $this->addFilesystemByAdapter($name, new FtpAdapter(
            FtpConnectionOptions::fromArray($config)
        ));
    }

    /**
     * Add a SFTP filesystem
     *
     * @param string $name name of filesystem
     * @param array $config config values (see SftpConnectionProvider)
     *
     * @return Filesystem
     */
    public function addSftpFilesystem($name, $config)
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

    }

    public function addFilesystemByAdapter($name, $adapter)
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
    public function getBasePath()
    {
        return cPath('/');
    }

    /**
     * Get the absolute path to the log directory
     *
     * @return string
     */
    public function getLogPath()
    {
        return cPath('/var/logs');
    }

    /**
     * Get the absolute path to the var directory
     *
     * @return string
     */
    public function getVarPath()
    {
        return cPath('/var');
    }

    /**
     * Get the absolute path to the cache directors
     *
     * @return string
     */
    public function getCachePath()
    {
        return cPath('/var/cache');
    }

    /**
     * Get the absolute path to the app directory
     *
     * @return string
     */
    public function getAppPath()
    {
        return $this->getModulePath('App');
    }

    /**
     * Get the absolute path to the module base directory
     *
     * @param string  $module  name of module
     *
     * @return string
     */
    public function getModulePath($module)
    {
        return C::get($module)->getBaseDirectory();
    }

    /**
     * Get the absolute path to the assets directory
     *
     * @return string
     */
    public function getAssetsPath()
    {
        return cPath('/assets');
    }

    /**
     * Get the absolute path to the data directory
     *
     * @return string
     */
    public function getDataPath()
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
    public function pathToUrl($path)
    {
        return str_replace(cPath('/'), C::Router()->getBaseUrl(), $path);
    }

    /**
     * Format absolute URL of file to path of file
     *
     * @param string $path the absolute path
     *
     * @return string
     */
    public function urlToPath($url)
    {
        return str_replace(C::Router()->getBaseUrl(), cPath('/'), $path);
    }

}