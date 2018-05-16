<?php
/**
 * This file contains the ViewFunctions class
 */

namespace Charm\Vivid\Helper;

use Carbon\Carbon;
use Charm\Vivid\Charm;

/**
 * Class ViewFunctions
 *
 * Adding basic view functions to twig views
 *
 * @package Charm\Vivid\Helper
 */
class ViewFunctions
{
    /** @var \Twig_Environment the twig instance */
    protected $twig;

    /**
     * ViewFunctions constructor.
     *
     * @param \Twig_Environment $twig the twig instance
     */
    function __construct($twig)
    {
        $this->twig = $twig;

        // Add functions
        $this->addBaseFunctions();
        $this->addFormHelpers();
        $this->addFormatters();
    }

    /**
     * Add base functions
     */
    public function addBaseFunctions()
    {
        // Add Asset URL function
        $this->twig->addFunction(new \Twig_SimpleFunction('getAssetUrl', function () {
            return Charm::Router()->getBaseUrl() . '/assets';
        }));

        // Add URL builder function
        $this->twig->addFunction(new \Twig_SimpleFunction('getUrl', function ($name, $args = []) {
            return Charm::Router()->buildUrl($name, $args);
        }));

        // Addb base URL function
        $this->twig->addFunction(new \Twig_SimpleFunction('getBaseUrl', function () {
            return Charm::Router()->getBaseUrl();
        }));
    }

    /**
     * Add form helpers
     */
    public function addFormHelpers()
    {
        // Add option to select
        // $val     -> option value
        // $display -> display text (empty -> val used)
        // $sel     -> selected value for comparision
        $this->twig->addFunction(new \Twig_SimpleFunction('formOption', function ($val, $display, $sel) {
            // Selected?
            $select = '';
            if($val == $sel) {
                $select = 'selected';
            }

            // Use value as display fallback
            if(empty($display)) {
                $display = $val;
            }

            return '<option value="' . $val . '" ' . $select . '>' . $display . '</option>';
        }));
    }

    /**
     * Add string formatters / manipulation functions
     */
    public function addFormatters()
    {
        $this->twig->addFunction(new \Twig_SimpleFunction('formatDate', function ($data) {
            return Charm::Formatter()->formatDate($data);
        }));

        $this->twig->addFunction(new \Twig_SimpleFunction('formatDateShort', function ($data) {
            return Charm::Formatter()->formatDateShort($data);
        }));

        $this->twig->addFunction(new \Twig_SimpleFunction('formatDateTimeShort', function ($data) {
            return Charm::Formatter()->formatDateTimeShort($data);
        }));

        $this->twig->addFunction(new \Twig_SimpleFunction('formatMoney', function ($data, $decimals = 2) {
            return Charm::Formatter()->formatMoney($data, $decimals);
        }));

        $this->twig->addFunction(new \Twig_SimpleFunction('date', function ($data) {
            return Carbon::parse($data);
        }));

    }

}