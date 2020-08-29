<?php
/**
 * This file contains the Handler class.
 */

namespace Charm\Vivid\Kernel;

use Charm\Vivid\Charm;
use Charm\Vivid\Exceptions\ModuleNotFoundException;
use Charm\Vivid\Exceptions\OutputException;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\Kernel\Interfaces\OutputInterface;
use Charm\Vivid\Kernel\Output\Json;
use Charm\Vivid\Kernel\Output\View;
use Charm\Vivid\Kernel\Traits\SingletonTrait;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Symfony\Component\Console\Application;

/**
 * Class Init
 *
 * Initializing the system.
 *
 * @package Charm\Vivid\Kernel
 */
class Handler
{
    use SingletonTrait;

    /**
     * @var object[] All module instances
     */
    protected $modules = [];

    /**
     * @var array All module class names for easy access
     */
    protected $module_classes = [];

    /** @var array All modules which should be loaded */
    protected $modules_to_load;

    /** @var array List with all blacklisted modules */
    protected $modules_blacklist;

    /**
     * Handler init for singleton
     */
    protected function init()
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
            'Database',
            'Request',
            'Formatter',
            'Cache' => '\\Charm\\Cache\\Cache',
            'Router' => '\\Charm\\Vivid\\Router\\Router',
            'Token' => '\\Charm\\Guard\\Token',
            'Guard' => '\\Charm\\Guard\\Guard',
            'Bob' => '\\Charm\\Bob\\Bob',
            'CharmCreator' => '\\Charm\\CharmCreator\\CharmCreator',
            'Mailman' => '\\Charm\\Mailman\\Mailman',
        ];

        $this->modules_blacklist = [];
    }

    /**
     * Init the system
     */
    private function initSystem()
    {
        // Include functions
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'Globals.php';
        require_once __DIR__ . DS . 'BaseFunctions.php';
        require_once __DIR__ . DS . 'HelperFunctions.php';

        // Let's init the base system
        $h = Handler::getInstance();

        // Blacklist modules that aren't needed in cli
        if (defined('CLI_PATH')) {
            $this->modules_blacklist = [
                'Session',
                'Request',
                '\\Charm\\Guard\\Token',
                '\\Charm\\Guard\\Guard'
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
    public function start()
    {
        // Save time for performance measurements
        $start_time = time();

        // Init the whole system
        $this->initSystem();

        $init_time = time();

        // System ready -> init Router
        $this->getModule('Router')->init();

        $routing_time = time();

        // Post init hooks
        $this->callPostInitHooks();

        // Save measurements
        if(Charm::Config()->inDebugMode()) {
            Charm::AppStorage()->set('Charm', 'time_start', $start_time);
            Charm::AppStorage()->set('Charm', 'time_init', $init_time);
            Charm::AppStorage()->set('Charm', 'time_routing', $routing_time);
        }

        // Route + Output!
        $this->output();

        // Finally shutdown
        $this->shutdown();
    }

    /**
     * Execute post init hooks of all modules
     */
    private function callPostInitHooks()
    {
        foreach($this->getModuleClasses() as $name => $class) {
            try {
                $mod = $this->getModule($name);
                if (is_object($mod) && method_exists($mod, 'postInit')) {
                    $mod->postInit();
                }
            } catch(ModuleNotFoundException $e) {
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
    public function startConsole()
    {
        // Init the whole system
        $this->initSystem();

        // TODO: Dynamize version string
        $app = new Application('Bob from Charm', '1.0');

        // Add commands from all modules (including the app itself)
        foreach($this->getModuleClasses() as $name => $module) {
            try {
                $mod = $this->getModule($name);
                if(is_object($mod) && method_exists($mod, 'getReflectionClass')) {
                    $dir = $mod->getBaseDirectory() . DS . 'Jobs' . DS . 'Console';
                    $namespace = $mod->getReflectionClass()->getNamespaceName() . "\\Jobs\\Console";

                    if(file_exists($dir)) {
                        $this->addConsoleCommands($app, $dir, $namespace);
                    }
                }
            } catch (\Exception $e) {
                // Console command not existing?
                // Just continue...
            }
        }

        // Start the console
        try {
            $app->run();
        } catch (\Exception $e) {
            echo "Got exception: " . $e->getMessage();
        }

        // Finally shutdown
        $this->shutdown();
    }

    /**
     * Add console commands
     *
     * @param Application $app console application instance
     * @param string $dir full path to the directory where the commands are stored
     * @param string $namespace the namespace for all commands
     */
    private function addConsoleCommands(&$app, $dir, $namespace)
    {
        $files = array_diff(scandir($dir), ['..', '.']);

        // Go through all files
        foreach ($files as $file) {
            $fullpath = $dir . DS . $file;
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
    private function addDependendModules($module = 'App')
    {
        // Get all modules defined in app config
        try {
            $modules = Charm::Config()->get($module . '#modules:modules');
        } catch (\Exception $e) {
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
    public function addModule($name)
    {
        if (!in_array($name, $this->modules_to_load)) {
            $this->modules_to_load[] = $name;
        }
    }

    /**
     * Load all modules
     */
    public function loadModules()
    {
        // Load all modules
        foreach ($this->modules_to_load as $name => $class) {
            $this->loadModule($name, $class);
        }
    }

    /**
     * Load a module
     *
     * @param string $name name of module
     * @param string $class class of module
     *
     * @return bool
     */
    public function loadModule($name, $class)
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
        if (!in_string("\\", $class)) {
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
    public function getModule($name)
    {
        if (!is_array($this->modules) || !array_key_exists($name, $this->modules)) {
            throw new ModuleNotFoundException('Module "' . $name . '" could not be found.');
        }

        return $this->modules[$name];
    }

    /**
     * Get class names of all loaded modules as array
     *
     * @return array
     */
    public function getModuleClasses()
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
    public function hasModule($name)
    {
        return in_array($name, $this->module_classes) || in_array($name, array_keys($this->module_classes));
    }

    /**
     * Call wanted method and output the response to the browser
     *
     * @return bool
     *
     * @throws \Exception
     */
    private function output()
    {
        // Call router dispatcher
        try {
            /** @var OutputInterface $response */
            $response = $this->getModule('Router')->dispatch();
        } catch (HttpRouteNotFoundException $e) {
            // Route not found
            return $this->outputError('RouteNotFound', 404);
        } catch (HttpMethodNotAllowedException $e) {
            // Route found, but method not allowed
            return $this->outputError('MethodNotAllowed', 403);
        } catch (ModuleNotFoundException $e) {
            // Invalid module
            return $this->outputError('ModuleNotFound', 501);
        }

        // Render method must exist
        if (!is_object($response) || !method_exists($response, 'render')) {
            Charm::Logging()->error('[OUTPUT] No output provided by method.');
            return $this->outputError('NoOutputProvided', 501);
        }

        // Output!
        try {
            echo $response->render();
            return true;
        } catch (\Exception $e) {
            return $this->outputError($e->getMessage(), 500);
        }
    }

    /**
     * Output an error
     *
     * @param string  $msg         error message
     * @param int     $statuscode  HTTP status code
     *
     * @return false
     *
     * @throws \Exception
     */
    private function outputError($msg, $statuscode = 500)
    {
        $error_style = $this->getModule('Config')->get('main:output.error_style', 'default');

        // Show whoops in debug mode
        $is_debug_mode = $this->getModule('Config')->get('main:debug.debugmode', false);
        $debug_exception = $this->getModule('Config')->get('main:debug.exceptions', true);

        if( ($is_debug_mode && $debug_exception) || $error_style == 'exception') {
            throw new OutputException($msg);
        }

        // Output JSON for API
        $http_accept = $this->getModule('Request')->get('HTTP_ACCEPT');
        if(in_string('json', $http_accept) || $error_style == 'json') {
            $output = Json::makeErrorMessage($msg, $statuscode);
            echo $output->render();

            return false;
        }

        // Output HTML in every other case
        $tpl = $this->getModule('Config')->get('main:output.error_view', 'error');
        $view = View::make($tpl, $statuscode)->with([
            'error_message' => $msg,
            'statuscode' => $statuscode
        ]);

        echo $view->render();
        return false;
    }

    /**
     * Shutdown the application because we're done!
     */
    private function shutdown()
    {
        exit(0);
    }

}