<?php
/**
 * This file contains the Handler class.
 */

namespace Charm\Vivid\Kernel;

use Charm\Bob\Commands\CronRunCommand;
use Charm\Vivid\Charm;
use Charm\Vivid\Exceptions\ModuleNotFoundException;
use Charm\Vivid\Exceptions\OutputException;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\Kernel\Interfaces\OutputInterface;
use Charm\Vivid\Kernel\Output\Json;
use Charm\Vivid\Kernel\Output\View;
use Charm\Vivid\Kernel\Traits\SingletonTrait;
use Charm\Vivid\PathFinder;
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
            'App' => '\\App\\Engine',
            'Config',
            'Logging',
            'Debug',
            'Session',
            'Database',
            'Request',
            'Formatter',
            'Router' => '\\Charm\\Vivid\\Router\\Router',
            'Guard' => '\\Charm\\Guard\\Guard',
            'Mailman',
            //'Cache'
        ];

        $this->modules_blacklist = [];
    }

    /**
     * Init the system
     */
    private function initSystem()
    {
        // Include functions
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'BaseFunctions.php';
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'HelperFunctions.php';

        // Let's init the base system
        $h = Handler::getInstance();

        // Blacklist modules that aren't needed in cli
        if (defined('CLI_PATH')) {
            $this->modules_blacklist = ['Session', 'Request'];
        }

        $h->loadModules();

        // Add dependand modules (e.g. app)
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
        // Init the whole system
        $this->initSystem();

        // Route + Output!
        $this->output();

        // Finally shutdown
        $this->shutdown();
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

        // Add base commands from bob

        // Get path by a console command
        try {
            $rc = new \ReflectionClass(CronRunCommand::class);
            $dir = dirname($rc->getFileName());
            $this->addConsoleCommands($app, $dir, "\\Charm\\Bob\\Commands");
        } catch (\Exception $e) {
            // Console command not existing? Then we don't have any charm commands at all!
            // Just continue...
        }

        // Add console commands from app
        $dir = PathFinder::getAppPath() . DIRECTORY_SEPARATOR . 'Jobs' . DIRECTORY_SEPARATOR . 'Console';
        $this->addConsoleCommands($app, $dir, "\\App\\Jobs\\Console");

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
            $fullpath = $dir . DIRECTORY_SEPARATOR . $file;
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
        } catch (ModuleNotFoundException $e) {
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
            // Class blacklisted?
            if (in_array($class, $this->modules_blacklist)) {
                // Just ignore it.
                continue;
            }

            // If no module name is set, use class name
            if (is_numeric($name)) {
                $mod_name = explode("\\", $class);
                $name = array_pop($mod_name);
            }

            // Ignore already loaded modules
            if (array_key_exists($name, $this->modules)) {
                continue;
            }

            // If no namespace is present, use own namespace
            if (!in_string("\\", $class)) {
                $class = "\\Charm\\Vivid\\Kernel\\Modules\\" . $class;
            }

            $mod = new $class;
            $mod->loadModule();

            // Save module instance
            $this->modules[$name] = $mod;
        }
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
        // Show whoops in debug mode
        if($this->getModule('Config')->get('main:debug.debugmode', false)) {
            throw new OutputException($msg);
        }

        // Output JSON for API
        $http_accept = $this->getModule('Request')->get('HTTP_ACCEPT');
        if(in_string('json', $http_accept)) {
            $output = Json::make([
                'type' => 'error',
                'error_message' => $msg
            ], $statuscode);
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