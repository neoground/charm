<?php
/**
 * This file contains the init class for mailman.
 */

namespace Charm\Mailman;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Helper\ViewExtension;
use Charm\Vivid\Kernel\Handler;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\FilesystemLoader;

/**
 * Class Mailman
 *
 * Mailman module
 *
 * @package Charm\Mailman
 */
class Mailman extends Module implements ModuleInterface
{
    /** @var MailmanDriverInterface the driver instance */
    protected $driver;

    /** @var string the driver name */
    protected $driver_name;

    /** @var mixed the passed driver data */
    protected $driver_data;

    /** @var Environment Twig instance */
    protected $twig;

    /** @var object the user instance if a user is added */
    protected $user;

    /**
     * Load the module
     */
    public function loadModule()
    {
        // Set default driver + connection
        $driver = C::Config()->get('connections:emails.default.driver', 'Smtp');
        $this->setDriver($driver, 'default');
        return true;
    }

    /**
     * Init twig
     *
     * Only called if we got an e-mail with template
     */
    private function initTwig()
    {
        $tpl_path = C::Storage()->getAssetsPath() . DS . 'templates' . DS . 'email';

        // Can be overridden by setting
        if (C::AppStorage()->has('Mailman', 'template_path')) {
            $tpl_path = C::AppStorage()->get('Mailman', 'template_path');
        }

        $loader = new FilesystemLoader($tpl_path);

        $twig = new Environment($loader, [
            'cache' => false,
            'debug' => C::Config()->get('main:debug.debugmode', false),
        ]);

        // Add charm global
        $twig->addGlobal('charm', C::getInstance());

        // Add charm twig extension
        $twig->addExtension(new ViewExtension());

        // Add own / custom twig functions (including app's ViewExtension)
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

        // Add string loader
        $twig->addExtension(new StringLoaderExtension());

        $this->twig = $twig;
    }

    /**
     * Magic methods from driver
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed|false
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->driver, $name)) {
            return call_user_func_array([$this->driver, $name], $arguments);
        }

        return false;
    }

    /**
     * Set the driver
     *
     * @param string $name driver name
     * @param mixed  $data optional data to pass to driver
     *
     * @return self
     */
    public function setDriver($name, $data = null)
    {
        $full_class = $name;
        if (!str_contains($full_class, '\\')) {
            // Got native driver
            $full_class = '\\Charm\\Mailman\\Drivers\\' . ucfirst($name);
        }

        if (class_exists($full_class)) {
            $this->driver = $full_class::compose($data);
            $this->driver_name = $name;
            $this->driver_data = $data;
        }

        return $this;
    }

    /**
     * Create a new e-mail
     *
     * This returns a cloned instance so no singleton values are changed
     *
     * @return Mailman
     */
    public function compose()
    {
        // Clone e-mail and re-set driver to prevent driver-related problems on mass emails
        $new_email = clone $this;
        $new_email->setDriver($this->driver_name, $this->driver_data);
        return $new_email;
    }

    /**
     * Add recipient
     *
     * @param string $email the e-mail address
     * @param string $name  (opt.) recipient name
     *
     * @return $this
     */
    public function addAddress($email, $name = '')
    {
        $this->driver->addAddress($email, $name);
        return $this;
    }

    /**
     * Add a CC address
     *
     * @param string $email the e-mail address
     * @param string $name  (opt.) recipient name
     *
     * @return $this
     */
    public function addCC($email, $name = '')
    {
        $this->driver->addCC($email, $name);
        return $this;
    }

    /**
     * Add a BCC address
     *
     * @param string $email the e-mail address
     * @param string $name  (opt.) recipient name
     *
     * @return $this
     */
    public function addBCC($email, $name = '')
    {
        $this->driver->addBCC($email, $name);
        return $this;
    }

    /**
     * Add a user as recipient
     *
     * @param object $user the user object
     *
     * @return $this
     */
    public function addUser($user)
    {
        $this->driver->addAddress($user->email, $user->getDisplayName());
        $this->user = $user;
        return $this;
    }

    /**
     * Add authenticated user as recipient
     *
     * @return $this
     */
    public function addCurrentUser()
    {
        $this->addUser(C::Guard()->getUser());
        return $this;
    }

    /**
     * Add an attachment
     *
     * @param string $path absolute path to file
     * @param string $name (opt.) filename of attachment
     *
     * @return $this
     */
    public function addAttachment($path, $name = '')
    {
        $this->driver->addAttachment($path, $name);
        return $this;
    }

    /**
     * Set content of message
     *
     * Text only for non-html email clients)
     *
     * @param string $msg the message
     *
     * @return $this
     */
    public function setTextContent($msg)
    {
        $this->driver->setTextContent($msg);
        return $this;
    }

    /**
     * Set HTML content of message
     *
     * @param string $msg the message
     *
     * @return $this
     */
    public function setHtmlContent($msg)
    {
        $this->driver->setHtmlContent($msg);
        return $this;
    }

    /**
     * Set e-mail subject
     *
     * @param string $name the subject
     *
     * @return $this
     */
    public function setSubject($name)
    {
        $this->driver->setSubject($name);
        return $this;
    }

    /**
     * Set HTML content by template
     *
     * @param string $name     name of template directory or single twig file (with .twig extension)
     * @param array  $data     (opt.) array of data to pass to template
     * @param bool   $combined (opt.) use html + text templates automatically?
     *
     * @return $this
     */
    public function setTemplate($name, $data = [], $combined = false)
    {
        // Init twig
        $this->initTwig();

        // Add mailman instance to twig data
        $data['mailman'] = $this;

        // Render template
        try {
            // Default case: single file
            $view = $name;

            // Got normal name -> directory?
            if (!str_contains($name, '.twig')) {
                $file = 'email.twig';

                if ($combined) {
                    $file = 'email_html.twig';
                }

                $view = $name . DS . $file;
            }

            if ($combined) {
                // Combined: First add text version
                $textview = str_replace('_html', '_text', $view);
                $this->setTextContent($this->twig->render($textview, $data));
            }

            $this->setHtmlContent($this->twig->render($view, $data));

        } catch (\Exception $e) {
            C::Logging()->error('Could not render e-mail template', [$name, $e->getMessage()]);
        }

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->driver->getBody();
    }

    /**
     * Get plain text body
     *
     * @return string
     */
    public function getTextBody()
    {
        return $this->driver->getTextBody();
    }

    /**
     * Get from string
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->driver->getFrom();
    }

    /**
     * Get the set user
     *
     * @return object|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Successfully sent email?
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->driver->isSuccess();
    }

    /**
     * Get error message
     *
     * @return null|string
     */
    public function getErrorMessage()
    {
        return $this->driver->getErrorMessage();
    }

    /**
     * Send the e-mail
     *
     * @return $this
     */
    public function send()
    {
        $this->driver->send();
        return $this;
    }

    /**
     * Get the driver
     *
     * @return MailmanDriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

}