<?php
/**
 * This file contains charm's helper functions for string, arrays and so on
 */

if (!function_exists('is_json')) {

    /**
     * Check if a string is valid json
     *
     * @param $string
     *
     * @return bool
     */
    function is_json($string): bool
    {
        if (is_array($string)) {
            return false;
        }

        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

}


if (!function_exists('is_serialized')) {

    /**
     * Check if a string is serialized
     *
     * @param string $string
     *
     * @return bool
     */
    function is_serialized($string): bool
    {
        return (@unserialize($string) !== false);
    }
}

if (!function_exists('ddd') && class_exists("\\Kint")) {

    /**
     * Dump and die
     *
     * @param mixed ...$vars
     */
    function ddd(...$vars)
    {
        // Handling of query debugging
        foreach ($vars as $k => $v) {
            if ($v instanceof \Illuminate\Database\Eloquent\Builder) {
                $vars[$k] = [
                    'query' => $v->toSql(),
                    'bindings' => $v->getBindings(),
                    'explain' => $v->explain(),
                    'builder' => $v,
                ];
            }
        }

        \Kint::dump(...$vars);
        \Charm\Vivid\C::shutdown();
    }
}

if (!function_exists('ddb')) {

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

if (!function_exists('to_string')) {

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
        if (is_object($input)) {
            return serialize($input);
        }

        // Array -> JSON
        if (is_array($input)) {
            return json_encode($input);
        }

        // Default: no conversion
        return $input;
    }

}

if (!function_exists('from_string')) {

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
        if (is_serialized($input)) {
            return unserialize($input);
        }

        // JSON -> Array
        if (is_json($input)) {
            return json_decode($input, true);
        }

        // Default: no conversion
        return $input;
    }

}

if (!function_exists('is_cli')) {

    /**
     * Check if script is executed on console (cli)
     *
     * @return bool
     */
    function is_cli(): bool
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
    function is_countable($c): bool
    {
        return is_array($c) || $c instanceof \Countable;
    }
}

if (!function_exists('str_contains_array')) {

    /**
     * Check if an element of an array is in a string
     *
     * A combination of str_contains and in_array
     *
     * @param string       $haystack the string which should contain something
     * @param array|string $needles  an array of needles the string should contain
     *
     * @return bool
     */
    function str_contains_array(string $haystack, array|string $needles): bool
    {
        if (is_array($needles)) {
            foreach ($needles as $n) {
                if (str_contains($haystack, $n)) {
                    return true;
                }
            }
        }

        return str_contains($haystack, $needles);
    }
}