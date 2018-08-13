<?php
/**
 * This file contains the init class for mailman.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Charm;
use Charm\Vivid\Helper\ViewExtension;
use Charm\Vivid\Kernel\Handler;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\PathFinder;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Class Logging
 *
 * Logging module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Mailman implements ModuleInterface
{
    /** @var PHPMailer PHPMailer instance */
    protected $mail;

    /** @var \Twig_Environment Twig instance */
    protected $twig;

    /**
     * Load the module
     */
    public function loadModule()
    {
        // Set default connection
        $this->setConnection('default');
        
        return true;
    }

    /**
     * Init twig
     *
     * Only called if we got an e-mail with template
     */
    private function initTwig()
    {
        $tpl_path = PathFinder::getAssetsPath() . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'email';

        $loader = new \Twig_Loader_Filesystem($tpl_path);

        $twig = new \Twig_Environment($loader, array(
            'cache' => false,
            'debug' => Charm::Config()->get('main:debug.debugmode', false)
        ));

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

        // Add string loader
        $twig->addExtension(new \Twig_Extension_StringLoader());

        $this->twig = $twig;
    }

    /**
     * Set SMTP connection
     *
     * @param string  $name  name of connection (defined in connections:email)
     *
     * @return $this
     */
    public function setConnection($name)
    {
        // Where do we find the config data?
        $configspace = 'connections:emails.' . $name;
        
        // Init PHPMailer and set config values
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->XMailer = 'Charm';

            $type = Charm::Config()->get($configspace . '.auth');

            if($type == 'sendmail') {
                // Just use sendmail
                $mail->isSendmail();

            } elseif($type == 'smtp') {
                // SMTP connection
                $mail->isSMTP();

                $mail->SMTPAuth = Charm::Config()->get($configspace . '.auth');
                $mail->AuthType = strtoupper(Charm::Config()->get($configspace . '.authtype', 'LOGIN'));
                $mail->Host = Charm::Config()->get($configspace . '.host');
                $mail->Username = Charm::Config()->get($configspace . '.username');
                $mail->Password = Charm::Config()->get($configspace . '.password');
                $mail->Port = Charm::Config()->get($configspace . '.port');

                // TLS / SSL security
                if (Charm::Config()->get($configspace . '.usetls')) {
                    $mail->SMTPSecure = 'tls';
                } elseif (Charm::Config()->get($configspace . '.usessl')) {
                    $mail->SMTPSecure = 'ssl';
                } else {
                    $mail->SMTPSecure = false;
                }

                // Allow self signed certificates
                if (Charm::Config()->get($configspace . '.trustall', false)) {
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];
                }
            }

            $mail->setFrom(
                Charm::Config()->get($configspace . '.frommail'),
                Charm::Config()->get($configspace . '.fromname')
            );

            // Debug mode
            if(Charm::Config()->get('main:debug.debugmode', false)) {
                $mail->SMTPDebug = 4;
            }
        } catch(Exception $e) {
            Charm::Logging()->error('Could not set SMTP connection', [$e->getMessage(), $mail->ErrorInfo]);
        }

        $this->mail = $mail;

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
        return clone $this;
    }

    /**
     * Add recipient
     *
     * @param string  $email  the e-mail address
     * @param string  $name   (opt.) recipient name
     *
     * @return $this
     */
    public function addAddress($email, $name = '')
    {
        $this->mail->addAddress($email, $name);
        return $this;
    }

    /**
     * Add a CC address
     *
     * @param string  $email  the e-mail address
     * @param string  $name   (opt.) recipient name
     *
     * @return $this
     */
    public function addCC($email, $name = '')
    {
        $this->mail->addCC($email, $name);
        return $this;
    }

    /**
     * Add a BCC address
     *
     * @param string  $email  the e-mail address
     * @param string  $name   (opt.) recipient name
     *
     * @return $this
     */
    public function addBCC($email, $name = '')
    {
        $this->mail->addBCC($email, $name);
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
        $this->mail->addAddress($user->email, $user->getName());
        return $this;
    }

    /**
     * Add authenticated user as recipient
     *
     * @return $this
     */
    public function addCurrentUser()
    {
        $this->addUser(Charm::Guard()->getUser());
        return $this;
    }

    /**
     * Add an attachment
     *
     * @param string  $path  absolute path to file
     * @param string  $name  (opt.) filename of attachment
     *
     * @return $this
     */
    public function addAttachment($path, $name = '')
    {
        try {
            $this->mail->addAttachment($path, $name);
        } catch(Exception $e) {
            Charm::Logging()->error(
                'Could not add attachment to e-mail',
                [$e->getMessage(), $this->mail->ErrorInfo]
            );
        }

        return $this;
    }

    /**
     * Mass mail option
     *
     * SMTP connection will not close after each email sent, reduces SMTP overhead.
     * Useful for sending many emails at once.
     *
     * @return $this
     */
    public function isMassMail()
    {
        $this->mail->SMTPKeepAlive = true;
        return $this;
    }

    /**
     * Set content of message
     *
     * Text only for non-html email clients)
     *
     * @param string  $msg  the message
     *
     * @return $this
     */
    public function setTextContent($msg)
    {
        $this->mail->AltBody = $msg;
        return $this;
    }

    /**
     * Set HTML content of message
     *
     * @param string  $msg  the message
     *
     * @return $this
     */
    public function setHtmlContent($msg)
    {
        $this->mail->isHTML(true);
        $this->mail->Body = $msg;
        return $this;
    }

    /**
     * Set e-mail subject
     *
     * @param string  $name  the subject
     *
     * @return $this
     */
    public function setSubject($name)
    {
        $this->mail->Subject = $name;
        return $this;
    }

    /**
     * Set HTML content by template
     *
     * @param string  $name     name of template directory
     * @param array   $data     (opt.) array of data to pass to template
     * @param bool    $combined (opt.) use html + text templates automatically?
     *
     * @return $this
     */
    public function setTemplate($name, $data = [], $combined = false)
    {
        // Init twig
        $this->initTwig();

        // Add mail data to twig data
        $data['email'] = $this->mail;

        // Render template
        try {
            $file = 'email.twig';

            if($combined) {
                $file = 'email_html.twig';
            }

            $this->mail->isHTML(true);
            $this->mail->Body = $this->twig->render($name . DIRECTORY_SEPARATOR . $file, $data);

            if($combined) {
                // Also add text version
                $file = 'email_text.twig';
                $this->mail->AltBody = $this->twig->render($name . DIRECTORY_SEPARATOR . $file, $data);
            }

        } catch (\Exception $e) {
            Charm::Logging()->error('Could not render e-mail template', [$name, $e->getMessage()]);
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
        return $this->mail->Body;
    }

    /**
     * Get text body (alt body)
     *
     * @return string
     */
    public function getTextBody()
    {
        return $this->mail->AltBody;
    }

    /**
     * Send the e-mail
     *
     * @return $this
     */
    public function send()
    {
        try {
            $this->mail->send();
        } catch(Exception $e) {
            Charm::Logging()->error('Could not send email', [$e->getMessage(), $this->mail->ErrorInfo]);
        }

        // Clear all recipients to prevent errors with multiple recipients on mass mails
        $this->mail->clearAllRecipients();

        return $this;
    }


}