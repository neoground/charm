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
class Users_Contract extends Migration
{
    /** @var string the table name */
    protected $tablename = 'UsersContract';

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

                // The contract
                $table->unsignedInteger('ContractID');

                // The optional coupon
                $table->unsignedInteger('CouponID')->nullable();

                // Contract start and end date
                $table->date('ContractStart');
                $table->date('ContractEnd');

                // Date of contract cancel
                $table->date('ContractCancel')->nullable();

                // Auto renew contract after end?
                $table->boolean('ContractRenew')->default(false);

                // Price per period
                $table->decimal('Price', 8, 2);
                $table->char('Period', 1); // Once, Week, Month, Quarter, Halfyear, Year

                // Credit of user
                $table->decimal('Credit', 6, 2);

                // Payment method
                $table->string('PaymentMethod');

                // Date of last and next payment
                $table->date('LastPayment')->nullable();
                $table->date('NextPayment')->nullable();

                // Optional comment
                $table->string('Comment')->nullable();

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