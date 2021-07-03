<?php
/**
 * This file contains the Migration class.
 */

namespace Charm\Vivid\Base;

use Charm\Vivid\C;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Migrations\Migration as IMigration;

/**
 * Class Migration
 *
 * Base migration class
 *
 * @package Charm\Vivid\Base
 */
class Migration extends IMigration
{
    /** @var string the table name */
    protected $tablename = '';

    /** @var Manager the database capsule */
    protected $database;

    /**
     * Migration constructor.
     *
     * Add database capsule connection
     */
    public function __construct()
    {
        $this->database = C::Database()->getDatabaseConnection()->getSchemaBuilder();
    }

    /**
     * Get the table name
     * @return string
     */
    public function getTableName()
    {
        return $this->tablename;
    }
}