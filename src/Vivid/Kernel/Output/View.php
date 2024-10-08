<?php
/**
 * This file contains the View output class
 */

namespace Charm\Vivid\Kernel\Output;

use Charm\Cache\CacheEntry;
use Charm\Vivid\C;
use Charm\Vivid\Helper\ViewExtension;
use Charm\Vivid\Helper\ViewLoader;
use Charm\Vivid\Kernel\Handler;
use Charm\Vivid\Kernel\Interfaces\HttpCodes;
use Charm\Vivid\Kernel\Interfaces\OutputInterface;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;

/**
 * Class View
 *
 * Creating a view output
 *
 * @package Charm\Vivid\Kernel\Output
 */
class View implements OutputInterface, HttpCodes
{
    /** @var array content array */
    protected $content = [];

    /** @var int status code */
    protected $statuscode;

    /** @var string template name */
    protected $tpl;

    /** @var Environment twig instance */
    protected $twig;

    /** @var string  the module delimiter */
    protected static $module_delimiter = "#";

    /** @var int should the output be cached? If so, the value is the cache duration in minutes */
    protected $cache_output = 0;

    /**
     * View constructor.
     *
     * Template names follow the typical dot-notation:
     *
     * - $tpl = "dashboard.index" -> view: app/Views/dashboard/index.twig
     * - $tpl = "MyModule:api.version" -> view: $MyModuleDir/Views/api/version.twig
     *
     * @param string    $tpl        Template name without extension
     * @param int|array $statuscode HTTP status code or optional with() array
     */
    function __construct($tpl, $statuscode = 200)
    {
        $this->tpl = $tpl;
        $this->twig = $this->initTwig();

        // Set status code or content depending on type
        if (is_array($statuscode)) {
            $this->content = $statuscode;
        } else {
            $this->statuscode = $statuscode;
        }

        C::AppStorage()->set('View', 'template_name', $tpl);
    }

    /**
     * Make a new view response
     *
     * Template names follow the typical dot-notation:
     *
     *  - $tpl = "dashboard.index" -> view: app/Views/dashboard/index.twig
     *  - $tpl = "MyModule:api.version" -> view: $MyModuleDir/Views/api/version.twig
     *
     * @param string    $tpl        The template name without extension
     * @param int|array $statuscode status code, default: 200 or optional with() array
     *
     * @return View
     */
    public static function make($tpl, $statuscode = 200)
    {
        return new self($tpl, $statuscode);
    }

    /**
     * Make a new error view response
     *
     * @param string $message    the error message
     * @param int    $statuscode (opt.) the status code, default: 500
     *
     * @return View
     */
    public static function makeError($message, $statuscode = 500)
    {
        $x = new self(C::Config()->get('main:output.error_view', '_base.error'), $statuscode);
        return $x->with(['error_message' => $message, 'statuscode' => $statuscode]);
    }

    /**
     * Init the twig instance
     *
     * @return Environment
     */
    private function initTwig()
    {
        $loader = new ViewLoader(C::Storage()->getAppPath() . DS . 'Views');

        // Add views of modules (except App) with module's name as namespace
        foreach (Handler::getInstance()->getModuleClasses() as $name => $module) {
            $mod = Handler::getInstance()->getModule($name);
            if (is_object($mod) && $name != 'App' && method_exists($mod, 'getBaseDirectory')) {
                // Depending on module type, the base dir path might be in app dir or not. We support both cases.
                $dir = $mod->getBaseDirectory() . DS . 'app' . DS . 'Views';
                $alt_dir = $mod->getBaseDirectory() . DS . 'Views';

                if (file_exists($dir)) {
                    $loader->addPath($dir, $name);
                } else if (file_exists($alt_dir)) {
                    $loader->addPath($alt_dir, $name);
                }
            }
        }

        $debug_mode = C::Config()->get('main:debug.debugmode', false);

        // Init environment
        $twig = new Environment($loader, [
            'cache' => C::Storage()->getCachePath() . DS . 'views',
            'debug' => $debug_mode,
        ]);

        // Add extensions
        $twig->addExtension(new StringLoaderExtension());

        // Add charm twig extension
        $twig->addExtension(new ViewExtension());

        // Add own / custom twig functions from all modules (including app's ViewExtension)
        foreach (Handler::getInstance()->getModuleClasses() as $name => $module) {
            try {
                $mod = Handler::getInstance()->getModule($name);
                if (is_object($mod) && method_exists($mod, 'getReflectionClass')) {
                    $class = $mod->getReflectionClass()->getNamespaceName() . "\\System\\ViewExtension";

                    if (class_exists($class)) {
                        $twig->addExtension(new $class);
                    }
                }
            } catch (\Exception $e) {
                // Not existing? Just continue
            }
        }

        return $twig;
    }

    /**
     * Add array which will be available in the view
     *
     * @param array $vars
     *
     * @return self
     */
    public function with(array $vars)
    {
        $this->content = array_merge($this->content, $vars);
        return $this;
    }

    /**
     * Set the status code
     *
     * @param int $code
     */
    public function withStatusCode($code)
    {
        $this->statuscode = $code;
    }

    /**
     * Build the final output which will be sent to the browser
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render()
    {
        // Fire event
        C::Event()->fire('View', 'renderStart');

        // Set status code
        http_response_code($this->statuscode);

        // Set charm data
        $this->setCharmData();

        // Return cached view?
        if ($this->cache_output > 0) {
            $key = 'View_' . $this->tpl;
            if (C::has('Cache')) {
                if (C::Cache()->has($key)) {
                    return C::Cache()->get($key);
                } else {
                    // Store in cache
                    $ce = new CacheEntry($key);
                    $ce->setValue($this->twig->render(
                        self::getTemplateByName($this->tpl),
                        $this->content
                    ));
                    $ce->setTags(['View_Cache']);

                    C::Cache()->setEntry($ce, $this->cache_output);
                }
            }
        }

        return $this->twig->render(
            self::getTemplateByName($this->tpl),
            $this->content
        );
    }

    /**
     * Get template path for usage in twig iself
     *
     * Contains support for modules
     *
     * @param string $tpl charm's template name (e.g. Module#foo.bar)
     *
     * @return string
     */
    public static function getTemplateByName(string $tpl)
    {
        $tpl_str = $tpl;
        $package_parts = explode(self::$module_delimiter, $tpl_str);
        if (count($package_parts) > 1) {
            $package = $package_parts[0];
            $tpl = $package_parts[1];

            $tpl_str = '@' . $package . '/' . $tpl;
        }

        return str_replace('.', '/', $tpl_str) . '.twig';
    }

    /**
     * Set charm data
     *
     * Will add header + body of modules, custom message
     */
    private function setCharmData()
    {
        // Add charm data to content (custom head / body of modules)
        $head = C::AppStorage()->get('View', 'add_head', []);
        $body = C::AppStorage()->get('View', 'add_body', []);

        if (!is_array($head)) {
            $head = [];
        }
        if (!is_array($body)) {
            $body = [];
        }

        $head_content = '';
        $body_content = '';

        foreach ($head as $n => $head_entry) {
            if (C::Config()->inDebugMode()) {
                $head_content .= '<!-- [MODULE] ' . $n . ' -->' . "\n";
            }
            $head_content .= $head_entry . "\n";
        }

        foreach ($body as $n => $body_entry) {
            if (C::Config()->inDebugMode()) {
                $body_content .= '<!-- [MODULE] ' . $n . ' -->' . "\n";
            }
            $body_content .= $body_entry . "\n";
        }

        $this->content['charm'] = [
            'head' => $head_content,
            'body' => $body_content,
        ];

        // Add optional message
        if (C::has('Session') && C::Session()->has('charm_message')) {
            $this->content['charm']['message'] = C::Session()->get('charm_message');
            C::Session()->delete('charm_message');
        }
    }

    /**
     * Check if a view exists
     *
     * @param string $name view name
     *
     * @return bool
     */
    public static function exists($name)
    {
        $rel_path = str_replace(".", DS, $name);
        return file_exists(C::Storage()->getAppPath() . DS . 'Views' . DS . $rel_path . '.twig');
    }

    /**
     * Add data to <head> part of view
     *
     * @param string $name name of data
     * @param string $val  data (most likely your html)
     */
    public static function addHead($name, $val)
    {
        $data = C::AppStorage()->get('View', 'add_head', []);
        $data[$name] = $val;
        C::AppStorage()->set('View', 'add_head', $data);
    }

    /**
     * Add data to the end of the <body> part of view
     *
     * @param string $name name of data
     * @param string $val  data (most likely your html)
     */
    public static function addBody($name, $val)
    {
        $data = C::AppStorage()->get('View', 'add_body', []);
        $data[$name] = $val;
        C::AppStorage()->set('View', 'add_body', $data);
    }

    /**
     * Clear the view output cache
     *
     * @param null|string $view name of view or null to remove all
     *
     * @return bool true if removed, false if cache is not enabled
     */
    public static function clearCache($view = null)
    {
        if (C::has('Cache')) {
            if (empty($view)) {
                C::Cache()->removeByTag('View_Cache');
            } else {
                C::Cache()->remove('View_' . $view);
            }

            return true;
        }
        return false;
    }

    /**
     * Cache the whole View output
     *
     * This will cache the final html site and serve it
     * statically from the cache for the specified time.
     *
     * To remove the cached view in case of content change,
     * see View::clearCache('view_name');
     *
     * @param int $duration
     *
     * @return $this
     */
    public function cacheOutput(int $duration = 1440)
    {
        $this->cache_output = $duration;
        return $this;
    }

    /**
     * Get the Twig environment instance
     *
     * @return Environment The Twig environment instance.
     */
    public function getTwigEnvironment(): Environment
    {
        return $this->twig;
    }

}