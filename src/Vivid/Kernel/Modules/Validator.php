<?php
/**
 * This file contains the Validator module.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Respect\Validation\Validator as RespectValidator;

/**
 * Class Validator
 *
 * Validation module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Validator extends Module implements ModuleInterface
{
    /**
     * Load the module
     */
    public function loadModule()
    {
        // Nothing to do here yet
    }

    /**
     * Get a new Respect-Validator instance for custom validations
     *
     * @see https://respect-validation.readthedocs.io/en/latest/
     *
     * @return RespectValidator
     */
    public function create(): RespectValidator
    {
        return RespectValidator::create();
    }

    public function validateEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function validateDomain(string $domain): bool
    {
        return (bool) filter_var($domain, FILTER_VALIDATE_DOMAIN);
    }

    public function validateMacAddress(string $mac): bool
    {
        return (bool) filter_var($mac, FILTER_VALIDATE_MAC);
    }

    public function validateUrl(string $url, bool $path_required = false, bool $query_required = false): bool
    {
        $flags = [];
        if($path_required) {
            $flags[] = FILTER_FLAG_PATH_REQUIRED;
        }
        if($query_required) {
            $flags[] = FILTER_FLAG_QUERY_REQUIRED;
        }

        return (bool) filter_var($url, FILTER_VALIDATE_URL, [
            'flags' => $flags
        ]);
    }

}