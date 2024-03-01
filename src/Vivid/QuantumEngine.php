<?php
/**
 * This file contains the QuantumEngine class.
 */

namespace Charm\Vivid;

use Charm\Vivid\Kernel\Handler;

/**
 * Class QuantumEngine
 *
 * This class provides methods to start and run the framework.
 * You only need to provide an autoloader (composer),
 * the quantum engine takes care of everything else.
 *
 * Call in the main index.php: `Charm\Vivid\QuantumEngine::ignite();`
 *
 * Call in the console bob.php: `Charm\Vivid\QuantumEngine::engageConsole($argv);`
 */
class QuantumEngine
{
    /**
     * Run the app for web-based requests (default)
     *
     * @return void
     */
    public static function ignite(): void
    {
        Handler::getInstance()->start();
    }

    /**
     * Run the console app for CLI access
     *
     * @param array $argv array of arguments passed to script
     *
     * @return void
     */
    public static function engageConsole(array $argv): void
    {
        set_time_limit(0);
        define("CLI_PATH", $argv[0]);
        Handler::getInstance()->startConsole();
    }
}