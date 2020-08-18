<?php
/**
 * This file contains the CLASSNAME model
 */

namespace App\Models;

use Charm\Vivid\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CLASSNAME
 *
 * CLASSNAME model
 *
 * @package App\Models
 */
class CLASSNAME extends Model
{
    /** @var string table name */
    protected $table = 'TABLENAME';

    /** @var array fields for mass insert */
    protected $fillable = ['id'];

    use SoftDeletes;

}