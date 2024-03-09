<?php
/**
 * This file contains the Model class
 */

namespace Charm\Vivid;

use Carbon\Carbon;
use Charm\Cache\CacheEntry;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;

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
    protected bool $set_by = true;

    /** @var bool enable caching for this entity? Can be overridden in connections.yaml */
    protected bool $caching = true;

    /**
     * The attributes which can be filtered
     *
     * @var string[]
     */
    protected array $filter_attributes = [];

    /**
     * The attributes which should be used for a query search
     *
     * @var string[]
     */
    protected array $search_attributes = [];

    /**
     * The attributes which should be updated by updateOrCreate by default
     *
     * @var string[]
     */
    protected array $update_fields = [];

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
     * @param int $id      id of entity
     * @param int $minutes minutes after cache expires
     *
     * @return mixed
     */
    public static function findWithCache($id, $minutes = 720)
    {
        $classname = str_replace("\\", ":", get_called_class());
        $key = "Model:" . $classname . ':' . $id;

        if (C::has('Cache')) {
            // Get from cache
            if (C::Cache()->has($key)) {
                return C::Cache()->get($key);
            }

            // Not existing -> save it
            $entry = new CacheEntry($key);
            $entry->setValue(self::find($id));
            $entry->setTags(['Models', 'Models:' . $classname]);
            C::Cache()->setEntry($entry, $minutes);

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
        if (C::has('Cache')
            && C::Config()->get('connections:database.caching', true)
            && $this->caching) {

            $classname = str_replace("\\", ":", get_called_class());
            $key = "Model:" . $classname . ':' . $this->id;
            C::Cache()->remove($key);

            // Remove all with class specific tag
            C::Cache()->removeByTag('Model:' . $classname);
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
        if (C::has('Guard') && $this->set_by) {

            if (!$this->exists) {
                // Creating
                if (Capsule::schema()->hasColumn($this->table, 'created_by')) {
                    $this->created_by = C::Guard()->getUserId();
                }
            }

            // Add updated by
            if (Capsule::schema()->hasColumn($this->table, 'updated_by')) {
                $this->updated_by = C::Guard()->getUserId();
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

    /**
     * Add query filters based on request input
     *
     * @return Builder
     */
    public static function filterBasedOnRequest()
    {
        $model = new static();
        $x = self::where($model->getKeyName(), '>', 0);

        // Add soft delete filters
        if (!empty(C::Request()->get('trashed'))) {
            $x->withTrashed();
        }
        if (!empty(C::Request()->get('onlytrashed'))) {
            $x->onlyTrashed();
        }

        if (property_exists($model, 'filter_attributes')) {
            // Go through all set filter attributes
            foreach ($model->filter_attributes as $k => $v) {

                // If only val is present, key is numeric. Default case: string
                if (is_numeric($k)) {
                    $k = $v;
                    $v = "string";
                }

                // Get value
                $val = C::Request()->get($k);

                // No filtering if value is empty (but allow 0 and handle range case)
                if (empty($val) && $v !== "range" && $val !== 0) {
                    continue;
                }

                // Remove "id-" prefix from id fields
                if (str_contains($k, '_id')) {
                    $val = str_replace("id-", "", $val);
                }

                // Custom callbacks (gets $x + $val as parameter)
                if (is_callable($v) && !is_string($v)) {
                    $v($x, $val);
                }

                // Add value to query
                switch ($v) {
                    case 'string':
                        $x->where($k, $val);
                        break;
                    case 'string_like':
                        $x->where($k, 'LIKE', "%" . $val . "%");
                        break;
                    case 'range':
                        // Value is FROM;TO
                        $parts = explode(";", $val);
                        if (count($parts) === 2) {
                            $x->whereBetween($k, $parts);
                        } else {
                            // Check for separate fields
                            $val1 = C::Request()->get($k . "_from");
                            $val2 = C::Request()->get($k . "_to");
                            if (!empty($val1) && !empty($val2)) {
                                $x->whereBetween($k, [$val1, $val2]);
                            }
                            if (!empty($val1) && empty($val2)) {
                                // Only "from" value
                                $x->where($k, ">=", $val1);
                            } else if (empty($val1) && !empty($val2)) {
                                // Only "to" value
                                $x->where($k, "<=", $val2);
                            }
                        }
                        break;
                    case 'array':
                        if (is_array($val) || is_iterable($val)) {
                            $x->whereIn($k, $val);
                        }
                        break;
                    case 'array_like':
                        if (is_array($val) || is_iterable($val)) {
                            foreach($val as $array_val) {
                                $x->where($k, 'LIKE', '%' . $array_val . '%');
                            }
                        }
                        break;
                    case 'isnull':
                        if (!empty($val)) {
                            $x->whereNull($k);
                        }
                        break;
                    case 'notnull':
                        if (!empty($val)) {
                            $x->whereNotNull($val);
                        }
                        break;
                }
            }
        }

        // Add search query
        $query = C::Request()->get('query', false);
        if (!empty($query) && property_exists($model, 'search_attributes')) {
            $search_att = $model->search_attributes;
            $x->where(function ($q) use ($search_att, $query) {
                foreach ($search_att as $val) {
                    $q->orWhere($val, 'LIKE', '%' . $query . '%');
                }
            });
        }

        return $x;
    }

    /**
     * Filter this model based on request input, fetch, format and paginate data
     *
     * A combination of filterBasedOnRequest() and getPaginatedData()
     *
     * @return array
     */
    public static function getFilteredPaginatedData()
    {
        $x = self::filterBasedOnRequest();
        return self::getPaginatedData($x);
    }

    /**
     * Use a query for this model, fetch, format and paginate data
     *
     * @param Builder $x the SQL query
     *
     * @return array
     */
    public static function getPaginatedData(Builder $x): array
    {
        // Pagination
        $page = (int)C::Request()->get('page', 1);
        $amount = (int)C::Request()->get('amount', 25);
        $max_amount = (int)C::Request()->get('max_amount', 1000);
        $skip = ($page - 1) * $amount;

        // Prevent fetching too much data
        if ($amount > $max_amount) {
            $amount = $max_amount;
        }

        // Add order by
        $order_by = C::Request()->get('order_by');
        $order_dir = C::Request()->get('order_dir');
        if (!empty($order_by)) {
            // Support for multiple orders
            $parts = explode(";", $order_by);
            foreach ($parts as $part) {
                $order_by = $part;
                if (str_contains($order_by, "_ASC")) {
                    $order_dir = 'ASC';
                    $order_by = str_replace("_ASC", "", $order_by);
                } else if (str_contains($order_by, "_DESC")) {
                    $order_dir = 'DESC';
                    $order_by = str_replace("_DESC", "", $order_by);
                }

                if (strtoupper($order_dir) !== "ASC") {
                    $order_dir = 'DESC';
                }

                if ($order_by == 'random') {
                    $x = $x->inRandomOrder();
                } else {
                    $x = $x->orderBy($order_by, $order_dir);
                }
            }
        }

        $total = $x->clone()->count();

        // Fetch data
        $x = $x->skip($skip)->take($amount)->get();

        // Format results and build pagination array
        $results = [];
        foreach ($x as $entry) {
            if (method_exists($entry, 'formatToArray')) {
                $results[] = $entry->formatToArray();
            } else if (method_exists($entry, 'formatAsArray')) {
                $results[] = $entry->formatAsArray();
            } else {
                $results[] = $entry->toArray();
            }
        }

        $last_page = ceil($total / $amount);

        $current_url = C::Router()->getCurrentUrl();

        $page_string = "page=" . $page;
        if (!str_contains($current_url, '&page=') && !str_contains($current_url, '?page=')) {
            if (str_contains($current_url, '?')) {
                $current_url .= '&' . $page_string;
            } else {
                $current_url .= '?' . $page_string;
            }
        }

        $prev_page_url = null;
        $next_page_url = null;
        $first_page_url = str_replace($page_string, "page=1", $current_url);
        $last_page_url = str_replace($page_string, "page=" . $last_page, $current_url);

        if ($page > 1) {
            $prev_page_url = str_replace($page_string, "page=" . ($page - 1), $current_url);
        }
        if ($page + 1 <= $last_page) {
            $next_page_url = str_replace($page_string, "page=" . ($page + 1), $current_url);
        }

        return [
            'total' => $total,
            'per_page' => $amount,
            'current_page' => $page,
            'last_page' => $last_page,
            'first_page_url' => $first_page_url,
            'last_page_url' => $last_page_url,
            'next_page_url' => $next_page_url,
            'prev_page_url' => $prev_page_url,
            'custom_page_url' => $prev_page_url,
            'data' => $results,
        ];
    }

    /**
     * JSON formatting with unescaped unicode
     *
     * This makes storage inside the database a lot easier
     *
     * @param mixed $value
     *
     * @return false|string
     */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Backup this model (table in database) to a backup file
     *
     * This will serialize the whole collection.
     *
     * You can restore it with the restoreBackup() method.
     *
     * @param null|string $destination optional absolute path to file. Leave empty for default handling
     *
     * @return int|false the number of bytes that were written to the file, or false on failure.
     */
    public static function backup(string|null $destination = null)
    {
        if (empty($destination)) {
            $self = new static();
            $filename = Carbon::now()->format('Y-m-d_H-i-s') . '_' . $self->table . '.bak';
            $dir = C::Storage()->getVarPath() . DS . 'backup' . DS . 'models';
        } else {
            $dir = dirname($destination);
            $filename = basename($destination);
        }

        C::Storage()->createDirectoriesIfNotExisting($dir);
        return file_put_contents($dir . DS . $filename, serialize(static::all()));
    }

    /**
     * Get a list of all available backups for this model
     *
     * @param null|string $dir optional path to directory where backups are stored
     *
     * @return array absolute paths to all backups in descending order (the latest entity first)
     */
    public static function availableBackups($dir = null)
    {
        if (empty($dir)) {
            $dir = C::Storage()->getVarPath() . DS . 'backup' . DS . 'models';
        }

        $self = new static();

        $bakfiles = [];
        foreach (C::Storage()->scanDir($dir, 1) as $bakfile) {
            if (str_contains($bakfile, '_' . $self->table . '.bak')) {
                $bakfiles[] = $dir . DS . $bakfile;
            }
        }

        return $bakfiles;
    }

    /**
     * Restore a backup
     *
     * @param string $filename absolute path to the backup file or 'latest' to use the latest backup
     */
    public static function restoreBackup(string $filename = 'latest')
    {
        if ($filename == 'latest') {
            $available = static::availableBackups();
            if (count($available) == 0) {
                return false;
            }

            $filename = array_shift($available);
        }

        if (!file_exists($filename)) {
            return false;
        }

        $collection = unserialize(file_get_contents($filename));

        // Insert all entries
        foreach ($collection as $entity) {
            $entity->save();
        }

        return true;
    }

    /**
     * Add or update an entity
     *
     * ID values (in both fields) can have "id-" prepended as values for better sorting,
     * this will be stripped from the values. Matches on id field in search fields and update fields
     * which contain "_id".
     *
     * Model will persist, no need to save manually.
     *
     * @param mixed|null $search_fields array of fields to match for entity (see eloquent's updateOrCreate) or id value
     *                                  or null for id request field
     * @param mixed|null $update_values array with table field names as key and new value as value or null to use
     *                                  "update_fields" array in class
     *
     * @return mixed the object of the entity, returns value of eloquent's updateOrCreate
     */
    public static function addOrUpdate(mixed $search_fields = null, $update_values = null)
    {
        $x = new static();
        if (!$update_values) {
            $update_values = C::Request()->getMultiple($x->update_fields);
        }

        if (!is_array($search_fields)) {
            if (is_numeric($search_fields)) {
                $id = $search_fields;
            } else {
                $id = C::Request()->get('id');
            }

            $id = str_replace("id-", "", $id);
            if (empty($id)) {
                $id = null;
            }

            $search_fields = [
                'id' => $id,
            ];
        }

        // Remove "id-" from values of id fields, might be prepended for better sorting
        foreach ($update_values as $k => $v) {
            if (str_contains($k, '_id')) {
                $val = str_replace("id-", "", $v);

                if (empty($val)) {
                    $val = null;
                }

                $update_values[$k] = $val;
            }
        }

        return static::updateOrCreate($search_fields, $update_values);
    }

}