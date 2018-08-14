<?php
/**
 * This file contains the Charm main access class
 */

namespace Charm\Vivid;

use App\Engine;
use Charm\Cache\Cache;
use Charm\Guard\Guard;
use Charm\Guard\Token;
use Charm\Vivid\Kernel\EngineManager;
use Charm\Vivid\Kernel\Handler;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Charm
 *
 * Abstraction layer for kernel and module access
 *
 * @method static Kernel\Modules\AppStorage AppStorage
 * @method static Kernel\Modules\Arrays Arrays
 * @method static Kernel\Modules\Config Config
 * @method static Kernel\Modules\Database Database
 * @method static Kernel\Modules\Debug Debug
 * @method static Kernel\Modules\Formatter Formatter
 * @method static Kernel\Modules\Logging Logging
 * @method static Kernel\Modules\Mailman Mailman
 * @method static Kernel\Modules\Redis Redis
 * @method static Kernel\Modules\Request Request
 * @method static Kernel\Modules\Session Session
 * @method static Router\Router Router
 * @method static Guard Guard
 * @method static Token Token
 * @method static Cache Cache
 * @method static EngineManager App
 *
 * @package Charm\Vivid
 */
class Charm
{
    /**
     * Get a loaded module
     *
     * @param string $name module name
     *
     * @return mixed
     */
    public static function get($name)
    {
        $handler = Handler::getInstance();
        return $handler->getModule($name);
    }

    /**
     * Check if a module is loaded
     *
     * @param string $name name of moduile or full class name
     *
     * @return bool
     */
    public static function has($name)
    {
        $handler = Handler::getInstance();
        return $handler->hasModule($name);
    }

    /**
     * Get this instance
     *
     * @return self
     */
    public static function getInstance()
    {
        return new self;
    }

    /**
     * Static call of a loaded module
     *
     * @param $name
     * @param $arguments
     *
     * @return ModuleInterface
     */
    public static function __callStatic($name, $arguments)
    {
        return self::get($name);
    }

    /**
     * Call of a loaded module
     *
     * @param $name
     * @param $arguments
     *
     * @return ModuleInterface
     */
    public function __call($name, $arguments)
    {
        return self::get($name);
    }

}