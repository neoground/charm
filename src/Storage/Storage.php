<?php
/**
 * This file contains the Storage class
 */

namespace Charm\Storage;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use League\Flysystem\FilesystemAdapter;

/**
 * Class Storage
 *
 * Module binding to Charm kernel
 *
 * @package Charm\Storage
 */
class Storage extends Module implements ModuleInterface
{
    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // TODO Load all specified storages and save Filesystem adapter instances in AppStorage
    }

    /**
     * @param $name
     *
     * @return bool|FilesystemAdapter
     */
    public function get($name = 'default')
    {
        return Charm::AppStorage()->get('Storage', 'filesystem_' . $name);
    }

}