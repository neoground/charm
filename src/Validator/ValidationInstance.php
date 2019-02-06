<?php
/**
 * This file contains the ValidationInstance class
 */

namespace Charm\Validator;

use Charm\Vivid\Charm;

/**
 * Class ValidationInstance
 *
 * Wrapper for all validation stuff.
 *
 * @package Charm\Validator
 */
class ValidationInstance
{
    /** @var array the fields */
    protected $fields;

    /** @var callable error funciton */
    protected $error_function;

    /**
     * ValidationInstance constructor.
     *
     * @param null|array $fields (opt.) fields to check
     *
     * @return $this
     */
    public function __construct($fields = null)
    {
        if(!empty($fields)) {
            $this->check($fields);
        }

        return $this;
    }

    /**
     * Set fields to check
     *
     * @param array $fields
     *
     * @return $this
     */
    public function check($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Validate all set fields
     *
     * @return true|array|mixed
     */
    public function validateAll()
    {
        foreach($this->fields as $field => $rule) {
            $ret = $this->validate($field, $rule);
            if($ret !== true) {
                return $this->handleError($field, $ret);
            }
        }

        return true;
    }

    /**
     * Validate a single field
     *
     * @param string $field the field
     * @param string $rule rule to apply
     *
     * @return true|array
     */
    public function validate($field, $rule)
    {
        // Internal method
        $method = 'is' . ucfirst($rule);
        if(method_exists($this, $method)) {
            $ret = $this->$method($field, $rule);
            if($ret !== true) {
                return $this->handleError($field, $ret);
            }

            return true;
        }

        // Rule not found
        return false;
    }

    /**
     * Set error callback method
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function onError(callable $callback)
    {
        $this->error_function = $callback;
        return $this;
    }

    /**
     * Handle validation error
     *
     * @param string $field field where error occured
     * @param string $error error description
     *
     * @return array
     */
    private function handleError($field, $error)
    {
        if(is_callable($this->error_function)) {
            return ($this->error_function)($field, $error);
        }

        return [
            'field' => $field,
            'error' => $error
        ];
    }

    /**
     * Get a field
     *
     * @param string $field
     *
     * @return mixed
     */
    private function getField($field)
    {
        return Charm::Request()->get($field);
    }

    /**
     * Rule: is required
     *
     * @param string $field
     *
     * @return bool|string
     */
    public function isRequired($field)
    {
        return !empty($this->getField($field)) ? true : 'Empty';
    }

    /**
     * Rule: is numeric
     *
     * @param string $field
     *
     * @return bool|string
     */
    public function isNumeric($field)
    {
        return is_numeric($this->getField($field)) ? true : 'NotNumeric';
    }





}