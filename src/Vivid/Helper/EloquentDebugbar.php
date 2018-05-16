<?php
/**
 * This file contains the EloquentDebugbar class.
 */

namespace Charm\Vivid\Helper;

use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Class EloquentDebugbar
 *
 * Adding database queries to debug bar
 *
 * @package Charm\Vivid\Helper
 */
class EloquentDebugbar extends PDOCollector
{
    /**
     * EloquentDebugbar constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->addConnection($this->getTraceablePdo(), 'Eloquent PDO');
    }

    /**
     * Get traceable PDO DebugBar collector
     * @return \DebugBar\DataCollector\PDO\TraceablePDO
     */
    protected function getTraceablePdo()
    {
        return new TraceablePDO($this->getEloquentPdo());
    }

    /**
     * Get the PDO element
     * @return \PDO
     */
    protected function getEloquentPdo()
    {
        return Capsule::connection()->getPdo();
    }

    /**
     * Set the name. Override function!
     * @return string
     */
    public function getName()
    {
        return "eloquent_pdo";
    }

    /**
     * Set the widgets. OVerride function!
     * @return array
     */
    public function getWidgets()
    {
        return [
            "eloquent" => [
                "icon" => "inbox",
                "widget" => "PhpDebugBar.Widgets.SQLQueriesWidget",
                "map" => "eloquent_pdo",
                "default" => "[]"
            ],
            "eloquent:badge" => [
                "map" => "eloquent_pdo.nb_statements",
                "default" => 0
            ]
        ];
    }
}