<?php
/**
 * This file contains the Charm main access class - The magic magnet
 */

namespace Charm\Vivid;

use Charm\Bob\Bob;
use Charm\Cache\Cache;
use Charm\CharmCreator\CharmCreator;
use Charm\Database\Database;
use Charm\DebugBar\DebugBar;
use Charm\Events\EventProvider;
use Charm\Guard\Guard;
use Charm\Guard\Token;
use Charm\Http\Http;
use Charm\Mailman\Mailman;
use Charm\Performance\Performance;
use Charm\Storage\Storage;
use Charm\Vivid\Kernel\EngineManager;
use Charm\Vivid\Kernel\Handler;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class C
 *
 * The magic magnet
 *
 * Abstraction layer for kernel and module access
 *
 * @method static Kernel\Modules\AppStorage AppStorage
 * @method static Kernel\Modules\Arrays Arrays
 * @method static Kernel\Modules\Config Config
 * @method static Database Database
 * @method static Kernel\Modules\Debug Debug
 * @method static Kernel\Modules\Formatter Formatter
 * @method static Kernel\Modules\Logging Logging
 * @method static Kernel\Modules\Redis Redis
 * @method static Kernel\Modules\Request Request
 * @method static Kernel\Modules\Server Server
 * @method static Kernel\Modules\Session Session
 * @method static Kernel\Modules\Validator Validator
 * @method static Router\Router Router
 * @method static DebugBar DebugBar
 * @method static Guard Guard
 * @method static Token Token
 * @method static Cache Cache
 * @method static Mailman Mailman
 * @method static Storage Storage
 * @method static EngineManager App
 * @method static EventProvider Event
 * @method static Http Http
 * @method static Performance Performance
 * @method static CharmCreator CharmCreator
 * @method static Bob Bob
 *
 * @package Charm\Vivid
 */
class C
{
    /** @var string the version of charm */
    public const VERSION = "3.4";

    /**
     * Get a loaded module
     *
     * @param string $name module name
     *
     * @return ModuleInterface
     *
     * @throws Exceptions\ModuleNotFoundException
     */
    public static function get(string $name): object
    {
        $handler = Handler::getInstance();
        return $handler->getModule($name);
    }

    /**
     * Get all loaded modules
     *
     * @return object[]
     */
    public static function getAllModules(): array
    {
        $handler = Handler::getInstance();
        return $handler->getAllModules();
    }

    /**
     * Check if a module is loaded
     *
     * @param string $name name of moduile or full class name
     *
     * @return bool
     */
    public static function has(string $name): bool
    {
        $handler = Handler::getInstance();
        return $handler->hasModule($name);
    }

    /**
     * Get this instance
     *
     * @return self
     */
    public static function getInstance(): self
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
     *
     * @throws Exceptions\ModuleNotFoundException
     */
    public static function __callStatic($name, $arguments): object
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
     *
     * @throws Exceptions\ModuleNotFoundException
     */
    public function __call($name, $arguments): object
    {
        return self::get($name);
    }

    /**
     * Shutdown the application
     *
     * Use this to gracefully shut down the application instead of die() or exit()
     */
    public static function shutdown(): void
    {
        Handler::getInstance()->shutdown();
    }

}