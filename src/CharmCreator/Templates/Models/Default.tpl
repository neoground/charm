---
name: Default model with table
fields:
  MODEL_NAME:
    name: Name of model
    type: input
  TABLE_NAME:
    name: Name of table
    type: input
---
<?php
/**
 * This file contains the MODEL_NAME model
 */

namespace TPL_NAMESPACE;

use Charm\Vivid\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class MODEL_NAME
 *
 * MODEL_NAME model
 */
class MODEL_NAME extends Model
{
    /** @var string The table associated with the model */
    protected $table = 'TABLE_NAME';

    /** @var string[]|bool The attributes that aren't mass assignable */
    protected $guarded = ['updated_at'];

    use SoftDeletes;

    public static function getTableStructure(): \Closure
    {
        return function (Blueprint $table) {
            $table->increments('id');

            // TODO Add table structure

            $table->timestamps();
            $table->softDeletes();
        };
    }

    public function formatToArray() : array
    {
        $arr = $this->toArray();
        return $arr;
    }

}