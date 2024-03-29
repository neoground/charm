<?php
/**
 * This file contains the DataTable output class
 */

namespace Charm\Vivid\Kernel\Output;

use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\OutputInterface;
use Charm\Vivid\Model;

/**
 * Class DataTable
 *
 * Creating a DataTable output
 *
 * @package Charm\Vivid\Kernel\Output
 */
class DataTable implements OutputInterface
{

    /** @var array  data to output as json */
    protected $data = [];

    /** @var int  status code */
    protected $statuscode;

    /** @var string  the model class */
    protected $model;

    /** @var array  all where conditions */
    protected $wheres = [];

    /** @var array  order columns */
    protected $order_cols = [];

    /** @var array  search columns */
    protected $search_cols = [];

    /** @var callable  the format data function */
    protected $callable;

    /** @var int the max length to return */
    protected $max_length;

    /**
     * Output factory
     *
     * @param int $statuscode (opt.) http status code (default: 200)
     *
     * @return self
     */
    public static function make($statuscode = 200)
    {
        $x = new self;
        $x->statuscode = $statuscode;

        return $x;
    }

    /**
     * Set the model
     *
     * @param string $model model class
     *
     * @return $this
     */
    public function withModel($model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set where condition(s)
     *
     * This will merge all existing where conditions
     *
     * @param string|array $where where array or key if $val is specified
     * @param null|string  $val   the value if where is the wanted key
     * @param null|string  $val2  another value in the wall
     *
     * @return $this
     */
    public function where($where, $val = null, $val2 = null)
    {
        if (!empty($val) && !empty($val2)) {
            // Support for classic eloquent where's ("field", ">", "10")

            if (is_array($val2)) {
                $val2 = implode("||", $val2);
            }

            $where = [$where => $val . " " . $val2];
        } else if (!empty($val)) {
            // Value set for normal where condition
            $where = [$where => $val];
        } else if (is_array($where)) {
            $where = [];
            // Separate multiple where's
            foreach ($where as $k => $v) {
                $this->wheres[] = [$k => $v];
            }
            // Already return because already added items
            return $this;
        }

        // Support for simple text
        if (!is_array($where)) {
            $where = [$where];
        }

        $this->wheres[] = $where;
        return $this;
    }

    /**
     * Set order columns
     *
     * @param array $columns the order columns
     *
     * @return $this
     */
    public function withOrderColumns($columns)
    {
        $this->order_cols = $columns;
        return $this;
    }

    /**
     * Set search columns
     *
     * @param array $columns the search columns
     *
     * @return $this
     */
    public function withSearchColumns($columns)
    {
        $this->search_cols = $columns;
        return $this;
    }

    /**
     * Set the format data callable
     *
     * This lambda function will format all the data for returning
     *
     * @param callable $callable
     *
     * @return $this
     */
    public function formatData($callable)
    {
        $this->callable = $callable;
        return $this;
    }

    /**
     * Set max length to return if length = -1 is requested
     *
     * By default it will return 1.000 entities to
     * prevent timeouts or overloads by returning too many
     * items at once.
     *
     * @param int $length
     *
     * @return $this
     */
    public function setMaxLength($length)
    {
        $this->max_length = $length;
        return $this;
    }

    /**
     * Build the response array with all the data
     */
    private function buildResponse()
    {
        // Get values
        $length = C::Request()->get('length', 10);
        $start = C::Request()->get('start');
        $draw = C::Request()->get('draw');

        $order = C::Request()->get('order');
        $order_dir = $order[0]['dir'];
        $order_column = $order[0]['column'];

        if (empty($order_dir)) {
            $order_dir = 'ASC';
        }
        if (empty($order_column)) {
            $order_column = 0;
        }

        // Get the order column
        $o_column = $this->order_cols[0];
        if (array_key_exists($order_column, $this->order_cols)) {
            $o_column = $this->order_cols[$order_column];
        }

        $search_value = C::Request()->get('search')['value'];

        /** @var Model $model */
        $model = $this->model;

        // Get total rows (with where conditions)
        $entities = false;

        // Add optional wheres
        if (count($this->wheres) > 0) {
            foreach ($this->wheres as $w) {
                $k = key($w);
                $w = $w[$k];

                // Like?
                $parts = explode(" ", $w);
                if ($parts[0] == 'LIKE') {
                    // Like
                    array_shift($parts);

                    if (!$entities) {
                        $entities = $model::where($k, 'LIKE', '%' . implode(" ", $parts) . '%');
                    } else {
                        $entities->where($k, 'LIKE', '%' . implode(" ", $parts) . '%');
                    }
                } else if ($parts[0] == 'IN') {
                    // Where In
                    $entries = explode("||", $parts[1]);

                    if (!$entities) {
                        $entities = $model::whereIn($k, $entries);
                    } else {
                        $entities->whereIn($k, $entries);
                    }

                } else if ($w == 'onlyTrashed') {

                    if (!$entities) {
                        $entities = $model::onlyTrashed();
                    } else {
                        $entities->onlyTrashed();
                    }

                } else if ($w == 'withTrashed') {

                    if (!$entities) {
                        $entities = $model::withTrashed();
                    } else {
                        $entities->withTrashed();
                    }

                } else if ($parts[0] == 'NOT') {
                    array_shift($parts);

                    if (!$entities) {
                        $entities = $model::where($k, '<>', implode(" ", $parts));
                    } else {
                        $entities->where($k, '<>', implode(" ", $parts));
                    }

                } else if ($parts[0] == 'NOTNULL') {
                    array_shift($parts);

                    if (!$entities) {
                        $entities = $model::whereNotNull(implode(" ", $parts));
                    } else {
                        $entities->whereNotNull(implode(" ", $parts));
                    }

                } else if (in_array($parts[0], ['>', '>=', '<', '<=']) && count($parts) > 1) {
                    if (!$entities) {
                        $entities = $model::where($k, $parts[0], $parts[1]);
                    } else {
                        $entities->where($k, $parts[0], $parts[1]);
                    }
                } else {
                    // Normal where
                    if (!$entities) {
                        $entities = $model::where($k, $w);
                    } else {
                        $entities->where($k, $w);
                    }

                }
            }
        } else {
            // Get all
            $entities = $model::all();
        }

        $total = $entities->count();

        // Get data
        $entities = $model::where(function ($q) use ($search_value) {
            // Search paramters
            $search_value = '%' . $search_value . '%';

            $q->where($this->search_cols[0], 'LIKE', $search_value);

            if (count($this->search_cols) > 1) {
                foreach ($this->search_cols as $k => $v) {
                    // Ignore first one because we already have it
                    if ($k == 0) {
                        continue;
                    }

                    // Add or
                    $q->orWhere($v, 'LIKE', $search_value);
                }
            }
        });

        // Add optional wheres
        foreach ($this->wheres as $w) {
            $k = key($w);
            $w = $w[$k];

            // Like?
            $parts = explode(" ", $w);
            if ($parts[0] == 'LIKE') {
                // Like
                array_shift($parts);
                $entities->where($k, 'LIKE', '%' . implode(" ", $parts) . '%');
            } else if ($parts[0] == 'IN') {
                // Where In
                $entries = explode("||", $parts[1]);

                if (!$entities) {
                    $entities = $model::whereIn($k, $entries);
                } else {
                    $entities->whereIn($k, $entries);
                }

            } else if ($parts[0] == 'onlyTrashed') {
                $entities->onlyTrashed();
            } else if ($parts[0] == 'withTrashed') {
                $entities->withTrashed();
            } else if ($parts[0] == 'NOTNULL') {
                array_shift($parts);
                $entities->whereNotNull(implode(" ", $parts));
            } else if ($parts[0] == 'NOT') {
                array_shift($parts);
                $entities->where($k, '<>', implode(" ", $parts));
            } else if (in_array($parts[0], ['>', '>=', '<', '<=']) && count($parts) > 1) {
                $entities->where($k, $parts[0], $parts[1]);
            } else {
                // Normal where
                $entities->where($k, $w);
            }
        }

        $entities = $entities->orderBy($o_column, $order_dir);

        // Count filtered elements
        $filtered = $entities->count();

        // Get wanted amount
        if ($length == -1) {
            // -1 -> get all
            // Length can be overridden by $this->max_length
            if (!empty($this->max_length)) {
                $entities = $entities->skip($start)->take($this->max_length)->get();
            } else {
                // Get 1.000 entities to prevent a too large set
                $entities = $entities->skip($start)->take(1000)->get();
            }
        } else {
            // Just get provided amount
            $entities = $entities->skip($start)->take($length)->get();
        }

        // Format data
        $callable = $this->callable;
        $e_data = $callable($entities);

        // Finally return data
        $this->data = [
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $e_data,
        ];
    }

    /**
     * Build the final output which will be sent to the browser
     *
     * @return string
     */
    public function render()
    {
        // Fire event
        C::Event()->fire('DataTable', 'renderStart');

        // Set status code
        http_response_code($this->statuscode);

        // Build data
        $this->buildResponse();

        // Output data
        $r = Json::make($this->data, $this->statuscode);
        return $r->render();
    }

}