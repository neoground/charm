<?php
/**
 * This file contains the Model class
 */

namespace Charm\Vivid;

use Charm\Cache\CacheEntry;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Class Model
 *
 * The base model
 *
 * @package Charm\Vivid
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
    /** @var bool set created_by / updated_by? */
    protected $set_by = true;

    /**
     * Disable population of created_by / updated_by fields for an entry
     *
     * @return $this
     */
    public function disableByFields()
    {
        $this->set_by = false;
        return $this;
    }

    /**
     * Override boot function
     */
    public static function boot()
    {
        parent::boot();


    }

    /**
     * Normal self::find($id) function, but with integrated cache!
     *
     * @param int  $id       id of entity
     * @param int  $minutes  minutes after cache expires
     *
     * @return mixed
     */
    public static function findWithCache($id, $minutes = 720)
    {
        $classname = str_replace("\\", ":", get_called_class());
        $key = "Model:" . $classname . ':' . $id;

        if(Charm::has('Cache')) {
            // Get from cache
            if(Charm::Cache()->has($key)) {
                return Charm::Cache()->get($key);
            }

            // Not existing -> save it
            $entry = new CacheEntry($key);
            $entry->setValue(self::find($id));
            $entry->setTags(['Models', 'Models:' . $classname]);
            Charm::Cache()->setEntry($entry, $minutes);

            return $entry->getValue();
        }

        return self::find($id);
    }

    /**
     * Get class name without namespace
     *
     * @return string|false false on error
     */
    public static function getClassName()
    {
        try {
            $x = static::class;
            $reflect = new \ReflectionClass($x);
            return $reflect->getShortName();
        } catch (\ReflectionException $e) {
            return false;
        }
    }

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

        // Update by fields
        $this->setByFields();

        // Save
        $ret = parent::save($options);

        // After save
        $this->afterSave();

        // Update this instance. Flush cache
        if(Charm::has('Cache')) {
            $classname = str_replace("\\", ":", get_called_class());
            $key = "Model:" . $classname . ':' . $this->id;
            Charm::Cache()->remove($key);
        }

        // Return
        return $ret;
    }

    /**
     * Set created_by / updated_by fields with current user
     */
    private function setByFields()
    {
        // Add created_by / updated_by only if guard is enabled
        if(Charm::has('Guard') && $this->set_by) {

            if($this->exists) {
                // Updating
                if (Capsule::schema()->hasColumn($this->table, 'updated_by')) {
                    $this->updated_by = Charm::Guard()->getUserId();
                }

            } else {
                // Creating
                if (Capsule::schema()->hasColumn($this->table, 'created_by')) {
                    $this->created_by = Charm::Guard()->getUserId();
                }
                // Add updated by
                if (Capsule::schema()->hasColumn($this->table, 'updated_by')) {
                    $this->updated_by = Charm::Guard()->getUserId();
                }
            }
        }
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