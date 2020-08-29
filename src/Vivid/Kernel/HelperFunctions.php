<?php
/**
 * This file contains charm's helper functions for string, arrays and so on
 */

if(!function_exists('in_string')) {

    /**
     * Check if a string contains a specific substring
     *
     * Since PHP 8.0 you can use the built-in command "str_contains".
     * So this function will be deprecated soon!
     *
     * @param string $needle   what we look for
     * @param string $haystack what we have
     *
     * @return bool
     */
    function in_string($needle, $haystack)
    {
        // Use built-in function if available
        // TODO: Activate this in Dec 20 when PHP8 is available
        /*if(function_exists('str_contains')) {
            return str_contains($haystack, $needle);
        }*/

        // Ignore arrays!
        if (is_array($needle) || is_array($haystack) || empty($needle)) {
            return false;
        }

        return strpos($haystack, $needle) !== false;
    }

}

if(!function_exists('is_json')) {

    /**
     * Check if a string is valid json
     *
     * @param $string
     *
     * @return bool
     */
    function is_json($string)
    {
        if(is_array($string)) {
            return false;
        }

        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

}


if(!function_exists('is_serialized')) {

    /**
     * Check if a string is serialized
     *
     * @param string $string
     *
     * @return bool
     */
    function is_serialized($string)
    {
        return (@unserialize($string) !== false);
    }
}

if(!function_exists('ddd') && class_exists("\\Kint")) {

    /**
     * Dump and die
     *
     * @param array ...$vars
     */
    function ddd(...$vars)
    {
        \Kint::dump(...$vars);
        exit;
    }

    \Kint::$aliases[] = 'ddd';

}

if(!function_exists('ddb')) {

    /**
     * Dump to DebugBar
     *
     * @param mixed ...$vars
     */
    function ddb(...$vars)
    {
        \Charm\Vivid\C::DebugBar()->debugVar(...$vars);
    }
}

if(!function_exists('to_string')) {

    /**
     * Objects / Array to string
     *
     * @param mixed $input the input
     *
     * @return string
     */
    function to_string($input)
    {
        // Serialize objects
        if(is_object($input)) {
            return serialize($input);
        }

        // Array -> JSON
        if(is_array($input)) {
            return json_encode($input);
        }

        // Default: no conversion
        return $input;
    }

}

if(!function_exists('from_string')) {

    /**
     * String (from to_string) to object / array / string
     *
     * @param string $input the input
     *
     * @return mixed
     */
    function from_string($input)
    {
        // Serialized -> Object
        if(is_serialized($input)) {
            return unserialize($input);
        }

        // JSON -> Array
        if(is_json($input)) {
            return json_decode($input, true);
        }

        // Default: no conversion
        return $input;
    }

}

if(!function_exists('is_cli')) {

    /**
     * Check if script is executed on console (cli)
     *
     * @return bool
     */
    function is_cli()
    {
        return php_sapi_name() == 'cli';
    }

}

if (!function_exists('is_countable')) {

    /**
     * Check if a value is countable
     *
     * @param mixed $c the value
     *
     * @return bool
     */
    function is_countable($c)
    {
        return is_array($c) || $c instanceof \Countable;
    }
}