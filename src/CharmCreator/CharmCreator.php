<?php
/**
 * This file contains the CharmCreator class
 */

namespace Charm\CharmCreator;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Symfony\Component\Yaml\Yaml;

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
     * @param string $path    absolute path to the controller file (including file extension)
     * @param array  $data    the data for replacing placeholders (keys are the placeholder names)
     * @param string $tplname (opt.) name of controller template
     *
     * @return bool|int false if method exists or template is not found, return of file_put_contents on success
     */
    public function addMethodToController(string $path, array $data = [], string $tplname = 'Default'): bool|int
    {
        $tpl = $this->getTemplate('methods', $tplname);

        // Stop if template or controller is not found
        if (empty($tpl) || !file_exists($path)) {
            return false;
        }

        // Stop if method already exists in controller
        $controller = file_get_contents($path);
        if (str_contains($controller, $data['METHOD_NAME'])) {
            return false;
        }

        $tpl = $this->extract($tpl, 'content');

        // Replace placeholders
        foreach ($data as $key => $value) {
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
     * Extract yaml / content part from template file content
     *
     * @param string $tpl  template file content
     * @param string $type extraction type: yaml / content
     *
     * @return string
     */
    public function extract(string $tpl, string $type = 'yaml'): string
    {
        $parts = explode("---\n", $tpl);
        $yaml = $parts[1];
        unset($parts[0]);
        unset($parts[1]);
        $tpl = implode("---\n", $parts);

        if ($type == 'yaml') {
            return $yaml;
        }

        return $tpl;
    }

    /**
     * Get a template
     *
     * @param string $type type of template (e.g. controller)
     * @param string $name (opt.) name of template
     *
     * @return bool|string false if template is not found
     */
    public function getTemplate(string $type, string $name = 'Default'): bool|string
    {
        $path = $this->getTemplatesDirectoryFor($type);

        if (!$path || !file_exists($path)) {
            return false;
        }

        // TODO Add support for template in own App namespace (app's var/templates/...)

        $tpl = $path . DS . str_replace('.tpl', '', $name) . '.tpl';

        if (!file_exists($tpl)) {
            // TODO Handle invalid file...
        }

        return file_get_contents($tpl);
    }

    /**
     * Get all available templates
     *
     * @param string $type wanted type
     *
     * @return array
     */
    public function getAvailableTemplates(string $type): array
    {
        $dir = $this->getTemplatesDirectoryFor($type);
        if ($dir) {
            $files = C::Storage()->scanDir($dir);

            $arr = [];

            foreach ($files as $file) {
                $filename = str_replace(".tpl", "", $file);
                $tpl_content = $this->getTemplate($type, $filename);
                $yaml = Yaml::parse($this->extract($tpl_content, 'yaml'));
                $arr[] = $yaml['name'] . ' [' . $filename . ']';
            }


            return $arr;
        }

        return [];
    }

    /**
     * Get the template directory for a specific type
     *
     * @param string $type wanted type
     *
     * @return bool|string path or false if not found
     */
    public function getTemplatesDirectoryFor(string $type): bool|string
    {
        try {
            $dir = self::getBaseDirectory();
        } catch (\ReflectionException $e) {
            return false;
        }

        $config = C::Config()->get('CharmCreator#types:types.' . $type);
        if (!is_array($config)) {
            return false;
        }

        return $dir . DS . 'Templates' . DS . ucfirst($config['name']);
    }

    /**
     * Create a new file
     *
     * @param string $type    wanted type
     * @param string $path    absolute path
     * @param array  $data    variables array
     * @param string $tplname name of template
     *
     * @return bool|int false on error
     */
    public function createFile(string $type, string $path, array $data, string $tplname): bool|int
    {
        $tpl = $this->getTemplate($type, $tplname);

        // Stop if template is not found or file already exists
        if (empty($tpl) || file_exists($path)) {
            return false;
        }

        // Extract template itself (remove yaml)
        $tpl = $this->extract($tpl, 'content');

        // Replace placeholders
        foreach ($data as $key => $value) {
            $tpl = str_replace($key, $value, $tpl);
        }

        // Create file with this template
        return file_put_contents($path, $tpl);
    }

}