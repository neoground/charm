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
     * @param null|OutputInterface optional output interface
     *
     * @return bool|int false if template / controller is not found, return of file_put_contents on success
     */
    public function addMethodToController($path, $data = [], $tplname = 'Default', $output = null)
    {
        $tpl = $this->getTemplate('controller', $tplname);

        // Stop if template or controller is not found
        if(empty($tpl) || !file_exists($path)) {
            return false;
        }

        // Stop if method already exists in controller
        $controller = file_get_contents($path);
        if(str_contains($controller, $data['METHOD_NAME'])) {
            if(is_object($output)) {
                $output->writeln('[IGNORE] Method exists: ' . $data['METHOD_NAME']);
            }
            return false;
        }

        if(is_object($output)) {
            $output->writeln('[ADDING] Method: ' . $data['METHOD_NAME']);
        }

        // Get method template
        $method_tpl_parts = explode("#METHOD-START", $tpl);
        $method_tpl_parts = $method_tpl_parts[1];
        $method_tpl_parts = explode("#METHOD-END", $method_tpl_parts);
        $tpl = $method_tpl_parts[0];

        // Replace placeholders
        foreach($data as $key => $value) {
            $tpl = str_replace($key, $value, $tpl);
        }

        // Append to controller

        // Remove closing "}" of class
        $parts = explode("}", $controller);
        array_pop($parts);

        $new_controller = implode("}", $parts);
        $new_controller .= $tpl;
        $new_controller .= "\n}";

        return @file_put_contents($path, $new_controller);
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
        $tpl = $this->getTemplate('controller', $tplname);

        // Stop if template is not found or file already exists
        if(empty($tpl) || file_exists($path)) {
            return false;
        }

        // Replace placeholders
        foreach($data as $key => $value) {
            $tpl = str_replace($key, $value, $tpl);
        }

        // Strip default method
        $tpl = preg_replace('/#METHOD-START[\s\S]+?#METHOD-END/', '', $tpl);

        // Create file with this template
        return file_put_contents($path, $tpl);
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
        $tpl = $this->getTemplate('model', $tplname);

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
        $tpl = $this->getTemplate('migration', $tplname);

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
        try {

            switch($type) {
                case 'controller':
                    $path = self::getBaseDirectory() . DS . 'Templates' . DS . 'Controllers' . DS . $name . '.php';
                    break;
                case 'model':
                    $path = self::getBaseDirectory() . DS . 'Templates' . DS . 'Models' . DS . $name . '.php';
                    break;
                case 'migration':
                    $path = self::getBaseDirectory() . DS . 'Templates' . DS . 'Migrations' . DS . $name . '.php';
                    break;
            }

        } catch (\ReflectionException $e) {
            return false;
        }

        if(!file_exists($path)) {
            return false;
        }

        return file_get_contents($path);
    }

}