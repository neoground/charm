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
class Users extends Migration
{
    /** @var string the table name */
    protected $tablename = 'Users';

    /**
     * Run the migration (if table is not existing)
     */
    public function up()
    {
        if (!$this->database->hasTable($this->tablename)) {
            $this->database->create($this->tablename, function (Blueprint $table) {
                $table->increments('ID');

                // Basic fields
                $table->string('Email');
                $table->string('Password');
                $table->string('Secret')->nullable(); // 2FA secret

                // User data
                $table->unsignedTinyInteger('Gender')->nullable(); // 1 - male, 2 - female, 3 - they, 4 - other

                // The display name
                $table->string('DisplayName')->nullable();

                // The real name
                $table->string('FirstName', 60)->nullable();
                $table->string('LastName', 80)->nullable();

                // API token
                $table->string('ApiToken')->nullable();

                // Role id
                $table->unsignedTinyInteger('RoleID')->default(1);

                // Account enabled?
                $table->boolean('Enabled')->default(false);

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