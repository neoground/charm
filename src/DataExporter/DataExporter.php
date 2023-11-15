<?php
/**
 * This file contains the DataExporter class
 */

namespace Charm\DataExporter;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class DataExporter
 *
 * Module binding to Charm kernel
 *
 * @package Charm\DataExporter
 */
class DataExporter extends Module implements ModuleInterface
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
     * Exporter factory
     *
     * @return Export
     */
    public function createNewExport(): Export
    {
        return new Export();
    }

    /**
     * Save an array as CSV
     *
     * The input array must contain sub arrays for each line.
     * The keys of the first sub array are used as the heading.
     *
     * This is a simple method for easy dumping.
     * For more extensive exports use the Export class instead.
     *
     * @param array  $arr         the input array
     * @param string $destination absolute path to csv file
     *
     * @return bool
     */
    public function arrayToCsv(array $arr, string $destination): bool
    {
        $fp = fopen($destination, 'w');

        // Add heading
        fputcsv($fp, array_keys($arr[0]));
        // Add content
        foreach ($arr as $line) {
            fputcsv($fp, $line);
        }
        // Close
        return fclose($fp);
    }

}