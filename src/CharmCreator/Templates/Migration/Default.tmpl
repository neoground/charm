---
name: Default migration
fields:
  CLASS_NAME:
    name: Name of class
    type: input
  TABLENAME:
    name: Name of table
    type: input
---

<?php
/**
 * This file contains a database table migration.
 */

namespace App\System\Migrations;

use Charm\Vivid\Base\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration class
 */
class CLASS_NAME extends Migration
{
    /** @var string the table name */
    protected $tablename = 'TABLENAME';

    /**
     * Run the migration (if table is not existing)
     */
    public function up()
    {
        if (!$this->database->hasTable($this->tablename)) {
            $this->database->create($this->tablename, function (Blueprint $table) {
                $table->increments('id');

                // TODO Add table schema

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Revert everything
     */
    public function down()
    {
        $this->database->dropIfExists($this->tablename);
    }
}