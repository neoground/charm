<?php
/**
 * This file contains the model class
 */

namespace Charm\Vivid;

/**
 * Class Model
 *
 * The base model
 *
 * @package Charm\Vivid
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
    /**
     * Handle saving of model
     *
     * @param array $options options for saving
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        // Before save
        $this->beforeSave();

        // Save
        $ret = parent::save($options);

        // After save
        $this->afterSave();

        // Return
        return $ret;
    }

    /**
     * Code to execute on model saving right before save()
     */
    public function beforeSave()
    {

    }

    /**
     * Code to execute on model saving after save()
     */
    public function afterSave()
    {

    }
}