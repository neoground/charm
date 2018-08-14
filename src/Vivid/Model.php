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
    /**
     * Override boot function
     */
    public static function boot()
    {
        parent::boot();

        // Add created_by / updated_by only if guard is enabled
        if(Charm::has('Guard')) {
            // Called on each create
            static::creating(function ($entity) {
                // Add created by
                if (Capsule::schema()->hasColumn($entity->table, 'created_by')) {
                    $entity->created_by = Charm::Guard()->getUserId();
                }
                // Add updated by
                if (Capsule::schema()->hasColumn($entity->table, 'updated_by')) {
                    $entity->updated_by = Charm::Guard()->getUserId();
                }
            });

            // Call on each update
            static::updating(function ($entity) {
                // Add updated by
                if (Capsule::schema()->hasColumn($entity->table, 'updated_by')) {
                    $entity->updated_by = Charm::Guard()->getUserId();
                }

                // Update this instance. Flush cache
                $classname = str_replace("\\", ":", get_called_class());
                $key = "Model:" . $classname . ':' . $entity->id;
                Charm::Cache()->remove($key);
            });
        }
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
            $x = new self;
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