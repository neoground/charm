<?php
/**
 * This file contains the init class for formatting tools.
 */

namespace Charm\Vivid\Kernel\Modules;

use Carbon\Carbon;
use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Formatter
 *
 * Formatter module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Formatter implements ModuleInterface
{
    /**
     * Module init
     */
    public function loadModule()
    {
        // Nothing to do here yet!
    }

    /**
     * Format a date localized in a format specified in main.yaml
     *
     * @param string|Carbon $data the date
     *
     * @return bool|string
     */
    public function formatDate($data)
    {
        if ($data instanceof Carbon) {
            // Nothing to do. Great!
            $date = $data;
        } else {
            try {
                $date = Carbon::parse($data);
            } catch (\Exception $e) {
                return '';
            }
        }

        if ($date->toDateString() == '0000-00-00') {
            return '';
        }

        return $date->formatLocalized(Charm::Config()->get('main:local.timestamps.date'));
    }

    /**
     * Format a date localized in a short format specified in main.yaml
     *
     * @param string|Carbon  $data  the date
     *
     * @return bool|string
     */
    public function formatDateShort($data)
    {
        if (!empty($data)) {
            if ($data == '0000-00-00 00:00:00' || $data == '0000-00-00') {
                return '-';
            }

            try {
                return Carbon::parse($data)->formatLocalized(Charm::Config()->get('main:local.timestamps.dateshort'));
            } catch (\Exception $e) {
                return '';
            }
        }
        return false;
    }

    /**
     * Format a date with time localized in a short format specified in main.yaml
     *
     * @param string|Carbon  $data  the date
     *
     * @return bool|string
     */
    public function formatDateTimeShort($data)
    {
        if (!empty($data)) {
            if ($data == '0000-00-00 00:00:00' || $data == '0000-00-00') {
                return '-';
            }

            try {
                return Carbon::parse($data)->formatLocalized(Charm::Config()->get('main:local.timestamps.datetimeshort'));
            } catch (\Exception $e) {
                return '';
            }
        }
        return false;
    }

    /**
     * Format money / currencies
     *
     * @param string  $cash      input value
     * @param int     $decimals  (opt.) the decimals (default: 2)
     *
     * @return int|string
     */
    public function formatMoney($cash, $decimals = 2)
    {
        if (!empty($cash)) {
            return number_format((float)$cash, $decimals,
                Charm::Config()->get('main:local.formatting.decimal'),
                Charm::Config()->get('main:local.formatting.thousands')
            );
        }
        return 0;
    }

    /**
     * Format bytes to B / KB / MB / GB / ...
     *
     * Code from: http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
     *
     * @param int  $bytes      input bytes
     * @param int  $precision  precision of return value
     *
     * @return string
     */
    public function formatBytes($bytes, $precision = 0)
    {
        $size = ['B','KB','MB','GB','TB','PB','EB','ZB','YB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$precision}f", $bytes / pow(1024, $factor)) . " " . @$size[$factor];
    }

}