<?php
/**
 * This file contains a database table migration.
 */

namespace Charm\Supervisor\System\Migrations;

use Charm\Vivid\Base\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration class
 */
class Users_Address extends Migration
{
    /** @var string the table name */
    protected $tablename = 'UsersAddress';

    /**
     * Run the migration (if table is not existing)
     */
    public function up()
    {
        if (!$this->database->hasTable($this->tablename)) {
            $this->database->create($this->tablename, function (Blueprint $table) {
                $table->increments('ID');

                // User id relation
                $table->unsignedInteger('UserID');

                // Adress data
                $table->string('Street', 120);
                $table->string('StreetAdditional', 120);
                $table->string('No', 20);

                $table->string('Zip', 11);
                $table->string('State', 50);
                $table->string('City', 100);

                // Country with ISO code
                $table->string('Country', 3);

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