<?php
/**
 * This file contains the twig view loader class
 */

namespace Charm\Vivid\Helper;

use Charm\Vivid\C;
use Twig\Loader\FilesystemLoader;

class ViewLoader extends FilesystemLoader
{
    protected function findTemplate(string $name, bool $throw = true)
    {
        // Support for own naming system (but leaving twig's one as fallback)
        if(!str_contains($name, '.twig')) {
            if(str_contains($name, '#')) {
                // Module delimiter found -> build path
                $parts = explode('#', $name);
                $name = C::Storage()->getModulePath($parts[0]) . DS . 'Views' . DS .
                    str_replace('.', '/', $name) . '.twig';
            } else {
                // Replace dots with slashes and add the .twig extension
                $name = str_replace('.', DS, $name) . '.twig';

                // Same for base dir via "~"
                $name = str_replace('~', C::Storage()->getBasePath(), $name);
            }
        }

        // Absolute paths
        if(file_exists($name) && is_file($name)) {
            return $name;
        }

        // Call the parent method with the modified name
        return parent::findTemplate($name, $throw);
    }
}
