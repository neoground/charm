<?php
/**
 * This file contains the Handler class.
 */

namespace Charm\Vivid\Kernel;

use Charm\Vivid\C;
use Charm\Vivid\Exceptions\ModuleNotFoundException;
use Charm\Vivid\Exceptions\OutputException;
use Charm\Vivid\Exceptions\ViewException;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\Kernel\Interfaces\OutputInterface;
use Charm\Vivid\Kernel\Output\Json;
use Charm\Vivid\Kernel\Output\View;
use Charm\Vivid\Kernel\Traits\SingletonTrait;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use JetBrains\PhpStorm\NoReturn;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Symfony\Component\Console\Application;
use Twig\Error\Error;

/**
 * Class Handler
 *
 * Handling system init, execution, shutdown.
 */
class Handler
{
    use SingletonTrait;

    /** @var object[] All module instances */
    protected array $modules = [];

    /** @var array All module class names for easy access */
    protected array $module_classes = [];

    /** @var array All modules which should be loaded */
    protected array $modules_to_load;

    /** @var array List with all blacklisted modules */
    protected array $modules_blacklist;

    /**
     * Handler init for singleton
     */
    protected function init(): void
    {
        // Default modules
        $this->modules_to_load = [
            'Arrays',
            'AppStorage',
            'Storage' => '\\Charm\\Storage\\Storage',
            'Server',
            'Event' => '\\Charm\\Events\\EventProvider',
            'App' => '\\App\\Engine',
            'Config',
            'Logging',
            'Debug',
            'DebugBar' => '\\Charm\\DebugBar\\DebugBar',
            'Session',
            'Redis',
            'Database' => '\\Charm\\Database\\Database',
            'Request',
            'Formatter',
            'Validator',
            'Cache' => '\\Charm\\Cache\\Cache',
            'Router' => '\\Charm\\Vivid\\Router\\Router',
            'Token' => '\\Charm\\Guard\\Token',
            'Guard' => '\\Charm\\Guard\\Guard',
            'Bob' => '\\Charm\\Bob\\Bob',
            'CharmCreator' => '\\Charm\\CharmCreator\\CharmCreator',
            'Mailman' => '\\Charm\\Mailman\\Mailman',
            'Http' => '\\Charm\\Http\\Http'
        ];

        $this->modules_blacklist = [];
    }

    /**
     * Init the system
     */
    private function initSystem(): void
    {
        // Include functions
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'BaseFunctions.php';
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'HelperFunctions.php';

        // Init the base system
        $h = Handler::getInstance();

        // Blacklist modules that aren't needed in cli
        if (defined('CLI_PATH')) {
            $this->modules_blacklist = [
                'Session',
                '\\Charm\\Guard\\Token',
                '\\Charm\\Guard\\Guard',
                '\\Charm\\DebugBar\\DebugBar',
            ];
        }

        $h->loadModules();

        // Add dependand modules of app (not earlier because now all modules are loaded and available)
        $h->addDependendModules('App');
        $h->loadModules();
    }

    /**
     * Let there be light.
     *
     * This method starts the whole system.
     */
    public function start(): void
    {
        // Save time for performance measurements
        $start_time = microtime(true);

        // Init the whole system
        $this->initSystem();

        $init_time = microtime(true);

        // System ready -> init Router
        $this->getModule('Router')->init();

        $routing_time = microtime(true);

        // Post init hooks
        $this->callPostInitHooks();

        // Save measurements
        if (C::Config()->inDebugMode()) {
            C::AppStorage()->set('Charm', 'time_start', $start_time);
            C::AppStorage()->set('Charm', 'time_init', $init_time);
            C::AppStorage()->set('Charm', 'time_routing', $routing_time);
        }

        // Route + Output!
        $this->output();

        // Finally shutdown
        $this->shutdown();
    }

    /**
     * Execute post init hooks of all modules
     */
    private function callPostInitHooks(): void
    {
        foreach ($this->getModuleClasses() as $name => $class) {
            try {
                $mod = $this->getModule($name);
                if (is_object($mod) && method_exists($mod, 'postInit')) {
                    $mod->postInit();
                }
            } catch (ModuleNotFoundException $e) {
                // Module not found -> ignore.
            }
        }
    }

    /**
     * Let there be light in the testing environment.
     *
     * This method starts the system for unit testing.
     */
    public function startTesting()
    {
        // Blacklist non-needed modules for testing
        $this->modules_blacklist = [
            'Session',
        ];

        // Init the whole system
        $this->initSystem();

        // Post init hooks
        $this->callPostInitHooks();
    }

    /**
     * Let there be light in the console.
     *
     * This method starts the whole console system.
     */
    public function startConsole(): void
    {
        // Init the whole system
        $this->initSystem();

        // Post init hooks
        $this->callPostInitHooks();

        $app = new Application('C::BOB', C::VERSION);

        // Add commands from all modules (including the app itself)
        foreach ($this->getModuleClasses() as $name => $module) {
            try {
                $mod = $this->getModule($name);
                if (is_object($mod) && method_exists($mod, 'getReflectionClass')) {
                    $dir = $mod->getBaseDirectory() . DS . 'Jobs' . DS . 'Console';
                    $namespace = $mod->getReflectionClass()->getNamespaceName() . "\\Jobs\\Console";

                    if (file_exists($dir)) {
                        $this->addConsoleCommands($app, $dir, $namespace);
                    }
                }
            } catch (Exception $e) {
                // Console command not existing?
                // Just continue...
            }
        }

        // Start the console
        try {
            $app->run();
        } catch (Exception $e) {
            echo "Got exception: " . $e->getMessage();
        }

        // Finally shutdown
        $this->shutdown();
    }

    /**
     * Add console commands
     *
     * @param Application $app       console application instance
     * @param string      $dir       full path to the directory where the commands are stored
     * @param string      $namespace the namespace for all commands
     */
    private function addConsoleCommands(Application &$app, string $dir, string $namespace): void
    {
        $files = array_diff(scandir($dir), ['..', '.']);

        // Go through all files
        foreach ($files as $file) {
            $fullpath = $dir . DS . $file;

            // Process subdirs
            if (is_dir($fullpath)) {
                $this->addConsoleCommands($app, $fullpath, $namespace . "\\" . $file);
                continue;
            }

            // Add console command if present in file
            $pathinfo = pathinfo($fullpath);
            require_once($fullpath);

            $class = $namespace . "\\" . $pathinfo['filename'];

            if (class_exists($class)) {
                $app->add(new $class);
            }
        }
    }

    /**
     * Add dependend modules defined in a module (e.g. app)
     *
     * @param string $module (opt.) name of module (default: App)
     *
     * @return bool
     */
    private function addDependendModules(string $module = 'App'): bool
    {
        // Get all modules defined in app config
        try {
            $modules = C::Config()->get($module . '#modules:modules');
        } catch (Exception $e) {
            // Invalid, so no dependency.
            return false;
        }

        if (is_array($modules)) {

            // Go through all modules
            foreach ($modules as $module) {

                // Add module if not empty
                if (!empty($module)) {
                    $this->addModule($module);

                    // Also add depedend modules of this module
                    $this->addDependendModules($module);
                }

            }

        }

        return true;
    }

    /**
     * Add a module
     *
     * @param string $name class name of module with full namespace
     */
    public function addModule(string $name): void
    {
        if (!in_array($name, $this->modules_to_load)) {
            $this->modules_to_load[] = $name;
        }
    }

    /**
     * Load all modules
     */
    public function loadModules(): void
    {
        // Load all modules
        foreach ($this->modules_to_load as $name => $class) {
            $this->loadModule($name, $class);
        }
    }

    /**
     * Load a module
     *
     * @param string $name  name of module
     * @param string $class class of module
     *
     * @return bool
     */
    public function loadModule(string $name, string $class): bool
    {
        // Class blacklisted?
        if (in_array($class, $this->modules_blacklist)) {
            // Just ignore it.
            return false;
        }

        // If no module name is set, use class name
        if (is_numeric($name)) {
            $mod_name = explode("\\", $class);
            $name = array_pop($mod_name);
        }

        // Ignore already loaded modules
        if (array_key_exists($name, $this->modules)) {
            return false;
        }

        // If no namespace is present, use own namespace
        if (!str_contains($class, "\\")) {
            $class = "\\Charm\\Vivid\\Kernel\\Modules\\" . $class;
        }

        $mod = new $class;
        $mod->loadModule();

        // Save module instance
        $this->modules[$name] = $mod;
        $this->module_classes[$name] = $class;

        // Load dependant modules
        $this->addDependendModules($name);

        return true;
    }

    /**
     * Get a module instance
     *
     * @param string $name name of module
     *
     * @return ModuleInterface
     *
     * @throws ModuleNotFoundException
     */
    public function getModule(string $name): object
    {
        if (!is_array($this->modules) || !array_key_exists($name, $this->modules)) {
            throw new ModuleNotFoundException('Module "' . $name . '" could not be found.');
        }

        return $this->modules[$name];
    }

    /**
     * Get all modules
     *
     * @return object[]
     */
    public function getAllModules(): array
    {
        return $this->modules;
    }

    /**
     * Get class names of all loaded modules as array
     *
     * @return array
     */
    public function getModuleClasses(): array
    {
        return $this->module_classes;
    }

    /**
     * Check if a module is loaded
     *
     * @param string $name name of moduile or full class name
     *
     * @return bool
     */
    public function hasModule(string $name): bool
    {
        return in_array($name, $this->module_classes) || in_array($name, array_keys($this->module_classes));
    }

    /**
     * Call wanted method and output the response to the browser
     *
     * @return bool
     *
     * @throws Exception
     */
    private function output(): bool
    {
        // Call router dispatcher
        try {
            /** @var OutputInterface $response */
            $response = $this->getModule('Router')->dispatch();

            // Time measurement
            if (C::Config()->inDebugMode()) {
                C::AppStorage()->set('Charm', 'time_controller', microtime(true));
            }

        } catch (HttpRouteNotFoundException $e) {
            // Route not found
            return $this->outputError('PageNotFound', 404);
        } catch (HttpMethodNotAllowedException $e) {
            // Route found, but method not allowed
            return $this->outputError('MethodNotAllowed', 403);
        } catch (ModuleNotFoundException $e) {
            // Invalid module
            return $this->outputError('ModuleNotFound', 501);
        } catch (ModelNotFoundException $e) {
            // Searched model was not found (firstOrFail / findOrFail)
            return $this->outputError($e->getMessage(), 404);
        } catch (Exception $e) {
            // Other exception
            return $this->outputError($e->getMessage(), 500);
        }

        // Render method must exist
        if (!is_object($response) || !method_exists($response, 'render')) {
            C::Logging()->error('[OUTPUT] No output provided by method.');
            return $this->outputError('NoOutputProvided', 501);
        }

        // Set current page as last for easier redirecting
        if (C::has('Session')) {
            C::Session()->set('charm_forelast_page', C::Session()->get('charm_last_page'));
            C::Session()->set('charm_last_page', C::Router()->getCurrentUrl());
        }

        // Output!
        try {
            // Render, but only output if we got any
            // (to prevent problems with non-standard output like file streams)
            $render_output = $response->render();
            if (!empty($render_output)) {
                echo $render_output;
            }
            return true;
        } catch (Exception $e) {
            // Pretty exception for twig views
            if ($this->shouldOutputException() && $e instanceof Error) {
                throw new ViewException($e->getFile(), $e->getLine(), $e->getMessage());
            }

            // Handling of firstOrFail() / findOrFail()
            if ($e instanceof ModelNotFoundException) {
                return $this->outputError($e->getMessage(), 404);
            }

            return $this->outputError($e->getMessage(), 500);
        }
    }

    /**
     * Check if exception should be outputted
     *
     * @return bool
     *
     * @throws ModuleNotFoundException
     */
    private function shouldOutputException(): bool
    {
        $error_style = $this->getModule('Config')->get('main:output.error_style', 'default');

        // Show whoops in debug mode
        $is_debug_mode = $this->getModule('Config')->get('main:debug.debugmode', false);
        $debug_exception = $this->getModule('Config')->get('main:debug.exceptions', true);

        return ($is_debug_mode && $debug_exception) || $error_style == 'exception';
    }

    /**
     * Output an error
     *
     * @param string $msg        error message
     * @param int    $statuscode HTTP status code
     *
     * @return false
     *
     * @throws Exception
     */
    private function outputError(string $msg, int $statuscode = 500): bool
    {
        if ($this->shouldOutputException()) {
            throw new OutputException($msg);
        }

        // Output JSON for API
        $error_style = $this->getModule('Config')->get('main:output.error_style', 'default');
        if ($this->getModule('Request')->accepts('json') || $error_style == 'json') {
            $output = Json::makeErrorMessage($msg, $statuscode);
            echo $output->render();

            return false;
        }

        // Output HTML in every other case
        $tpl = $this->getModule('Config')->get('main:output.error_view', 'error');
        $view = View::make($tpl, $statuscode)->with([
            'error_message' => $msg,
            'statuscode' => $statuscode,
        ]);

        echo $view->render();
        return false;
    }

    /**
     * Shutdown the application because we're done!
     */
    #[NoReturn] public function shutdown(): void
    {
        // Fire shutdown event
        if (C::has('Event')) {
            C::Event()->fire('Charm', 'shutdown');
        }

        exit(0);
    }

}