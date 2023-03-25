<?php
/**
 * This file contains the CharmCreator class
 */

namespace Charm\CharmCreator;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CharmCreator
 *
 * Module binding to Charm kernel
 *
 * @package Charm\DataExporter
 */
class CharmCreator extends Module implements ModuleInterface
{
    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // Nothing to do here yet.
    }

    /**
     * Add a method to an existing controller
     *
     * @param string $path absolute path to the controller file (including file extension)
     * @param array $data the data for replacing placeholders (keys are the placeholder names)
     * @param string $tplname (opt.) name of controller template
     *
     * @return bool|int false if method exists or template is not found, return of file_put_contents on success
     */
    public function addMethodToController($path, $data = [], $tplname = 'Default')
    {
        $tpl = $this->getTemplate('methods', $tplname);

        // Stop if template or controller is not found
        if(empty($tpl) || !file_exists($path)) {
            return false;
        }

        // Stop if method already exists in controller
        $controller = file_get_contents($path);
        if(str_contains($controller, $data['METHOD_NAME'])) {
            return false;
        }

        // Replace placeholders
        foreach($data as $key => $value) {
            $tpl = str_replace($key, $value, $tpl);
        }

        // Append to controller
        $controller = preg_replace('/}\s*$/', $tpl . "\n}", $controller, -1, $count);

        if ($count === 0) {
            return false;
        }

        return file_put_contents($path, $controller);
    }

    /**
     * Create a new controller
     *
     * @param string $path absolute path to the new controller file (including file extension)
     * @param array $data the data for replacing placeholders (keys are the placeholder names)
     * @param string $tplname (opt.) name of controller template
     *
     * @return bool|int false if template is not found or controller already exists, return of file_put_contents on success
     */
    public function createController($path, $data = [], $tplname = 'Default')
    {
        $data = [
            'CLASSNAMESPACE' => 'App\\Controllers',
            ...$data
        ];
        return $this->createFile('controller', $path, $data, $tplname);
    }

    /**
     * Create a new model file
     *
     * @param string $path absolute path to the new file (including file extension)
     * @param array $data the data for replacing placeholders (keys are the placeholder names)
     * @param string $tplname (opt.) name of template
     *
     * @return bool|int false if template is not found or controller already exists, return of file_put_contents on success
     */
    public function createModel($path, $data = [], $tplname = 'Default') {
        return $this->createFile('model', $path, $data, $tplname);
    }

    /**
     * Create a new migration file
     *
     * @param string $path absolute path to the new file (including file extension)
     * @param array $data the data for replacing placeholders (keys are the placeholder names)
     * @param string $tplname (opt.) name of template
     *
     * @return bool|int false if template is not found or controller already exists, return of file_put_contents on success
     */
    public function createMigration($path, $data = [], $tplname = 'Default') {
        return $this->createFile('migration', $path, $data, $tplname);
    }

    /**
     * Get a template
     *
     * @param string $type type of template (e.g. controller)
     * @param string $name (opt.) name of template
     *
     * @return bool|string false if template is not found
     */
    public function getTemplate($type, $name = 'Default')
    {
        $path = $this->getTemplatesDirectoryFor($type);

        if(!$path || !file_exists($path)) {
            return false;
        }

        // TODO Add support for template in own App namespace (app's var/templates/...)

        return file_get_contents($path);
    }

    public function getAvailableTemplates($type): array
    {
        $dir = $this->getTemplatesDirectoryFor($type);
        if($dir) {
            $files = C::Storage()->scanDir($dir);
            return array_map(fn($file) => pathinfo($file, PATHINFO_FILENAME), $files);
        }

        return [];
    }

    public function getTemplatesDirectoryFor($type): bool|string
    {
        try {
            $dir = self::getBaseDirectory();
        } catch (\ReflectionException $e) {
            return false;
        }

        return match ($type) {
            'controller' => $dir . DS . 'Templates' . DS . 'Controllers',
            'methods' => $dir . DS . 'Templates' . DS . 'Methods',
            'model' => $dir . DS . 'Templates' . DS . 'Models',
            'migration' => $dir . DS . 'Templates' . DS . 'Migrations',
            default => false,
        };
    }

    public function createFile($type, $path, $data, $tplname): bool|int
    {
        $tpl = $this->getTemplate($type, $tplname);

        // Stop if template is not found or file already exists
        if(empty($tpl) || file_exists($path)) {
            return false;
        }

        // Replace placeholders
        foreach($data as $key => $value) {
            $tpl = str_replace($key, $value, $tpl);
        }

        // Create file with this template
        return file_put_contents($path, $tpl);
    }

}