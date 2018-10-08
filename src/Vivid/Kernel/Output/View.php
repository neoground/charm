<?php
/**
 * This file contains the View output class
 */

namespace Charm\Vivid\Kernel\Output;

use Charm\Vivid\Charm;
use Charm\Vivid\Helper\ViewExtension;
use Charm\Vivid\Kernel\Handler;
use Charm\Vivid\Kernel\Interfaces\HttpCodes;
use Charm\Vivid\Kernel\Interfaces\OutputInterface;
use Charm\Vivid\Kernel\Interfaces\ViewExtenderInterface;
use Charm\Vivid\PathFinder;

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

    /** @var array charm content array */
    protected $charm_content = [];

    /** @var int status code */
    protected $statuscode;

    /** @var string template name */
    protected $tpl;

    /** @var \Twig_Environment twig instance */
    protected $twig;

    /**
     * View constructor.
     *
     * @param string    $tpl        Template name without extension (folder separated by .)
     * @param int|array $statuscode HTTP status code or optional with() parameters
     */
    function __construct($tpl, $statuscode = 200)
    {
        $this->tpl = $tpl;
        $this->twig = $this->initTwig();

        // Set status code or content depending on type
        if(is_array($statuscode)) {
            $this->content = $statuscode;
        } else {
            $this->statuscode = $statuscode;
        }

        $this->charm_content = [
            'head' => [],
            'body' => []
        ];
    }

    /**
     * Make a new view response
     *
     * @param string    $tpl           The template name without extension (folder separated by .)
     * @param int|array $statuscode    status code, default: 200 or optional with() parameters
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
     * @param string $message the error message
     * @param int $statuscode (opt.) the status code, default: 500
     *
     * @return View
     */
    public static function makeError($message, $statuscode = 500)
    {
        $x = new self(Charm::Config()->get('main:output.error_view', '_base.error'), $statuscode);
        return $x->with(['error_message' => $message, 'statuscode' => $statuscode]);
    }

    /**
     * Init the twig instance
     *
     * @return \Twig_Environment
     */
    private function initTwig()
    {
        $loader = new \Twig_Loader_Filesystem(PathFinder::getAppPath() . DS . 'Views');

        $debug_mode = Charm::Config()->get('main:debug.debugmode', false);

        // Init environment
        $twig = new \Twig_Environment($loader, [
            'cache' => PathFinder::getCachePath() . DS . 'views',
            'debug' => $debug_mode
        ]);

        // Add extensions
        $twig->addExtension(new \Twig_Extension_StringLoader());

        // Add charm global
        $twig->addGlobal('charm', Charm::getInstance());

        // Add charm twig extension
        $twig->addExtension(new ViewExtension());

        // Add own / custom twig functions (including app's ViewExtension)
        foreach(Handler::getInstance()->getModuleClasses() as $name => $module) {
            try {
                $mod = Handler::getInstance()->getModule($name);
                if(is_object($mod) && method_exists($mod, 'getReflectionClass')) {
                    $class = $mod->getReflectionClass()->getNamespaceName() . "\\System\\ViewExtension";

                    if(class_exists($class)) {
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
     * Add view / twig extensions from all modules
     */
    private function addExtensionsFromModules()
    {
        // Add twig extensions from modules
        $modules = Handler::getInstance()->getModuleClasses();

        $loaded_extenders = [];
        foreach($modules as $module) {

            // Build class name (replace last part after namespace)
            $view_extender_classname_tmp1 = explode("\\", $module);
            // Remove last element (class name)
            array_pop($view_extender_classname_tmp1);

            // Replace last part with ViewExtender class
            $view_extender_classname = implode("\\", $view_extender_classname_tmp1) . "\\ViewExtender";

            // Load view extender if existing and not loaded yet
            if(class_exists($view_extender_classname) && !in_array($view_extender_classname, $loaded_extenders)) {
                // Init this class so twig can be extended
                $loaded_extenders[] = $view_extender_classname;

                /** @var ViewExtenderInterface $ve */
                $ve = new $view_extender_classname();

                // Extend twig
                $ve->extendTwig($this->twig);

                // Add head + body data
                $this->charm_content['head'][] = $ve->addHeadData();
                $this->charm_content['body'][] = $ve->addBodyData();
            }
        }
    }

    /**
     * Build the final output which will be sent to the browser
     *
     * @return string
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function render()
    {
        // Set status code
        http_response_code($this->statuscode);

        // Add modules data
        $this->addExtensionsFromModules();

        // Add charm data to content
        $this->content['charm'] = [
            'head' => implode("\n", $this->charm_content['head']),
            'body' => implode("\n", $this->charm_content['body'])
        ];

        // Add optional message
        if(Charm::Session()->has('charm_message')) {
            $this->content['charm']['message'] = Charm::Session()->get('charm_message');
            Charm::Session()->delete('charm_message');
        }

        return $this->twig->render(
            str_replace('.', DS, $this->tpl) . '.twig',
            $this->content
        );
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
        // TODO Implement
    }

}