<?php
/**
 * This file contains the mailman smtp driver
 */

namespace Charm\Mailman\Drivers;

use Charm\Mailman\MailmanDriverInterface;
use Charm\Vivid\Charm;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Class Smtp
 *
 * SMTP driver for Mailman
 *
 * @package Charm\Mailman\Drivers
 */
class Smtp implements MailmanDriverInterface
{
    /** @var PHPMailer PHPMailer instance */
    protected $mail;

    /** @var string from data */
    protected $from;

    /** @var bool sent success */
    protected $success;

    /** @var string|null the error message */
    protected $error_msg;

    /**
     * Driver factory
     *
     * @param null|mixed $data optional data to pass to driver
     *
     * @return self
     */
    public static function compose($data = null)
    {
        $x = new self;
        $x->setConnection($data);
        return $x;
    }

    /**
     * Add recipient
     *
     * @param string $email the e-mail address
     * @param string $name (opt.) recipient name
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
     * @param string $email the e-mail address
     * @param string $name (opt.) recipient name
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
     * @param string $email the e-mail address
     * @param string $name (opt.) recipient name
     *
     * @return $this
     */
    public function addBCC($email, $name = '')
    {
        $this->mail->addBCC($email, $name);
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
     * Set plain text content of message
     *
     * @param string $msg the message
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
     * @param string $msg the message
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
     * @param string $name the subject
     *
     * @return $this
     */
    public function setSubject($name)
    {
        $this->mail->Subject = $name;
        return $this;
    }

    /**
     * Set reply to
     *
     * @param string $email the email address
     * @param string $name  the name
     *
     * @return $this
     */
    public function setReplyTo($email, $name)
    {
        $this->mail->addReplyTo($email, $name);
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
     * Get from string
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->mail->From;
    }

    /**
     * Successfully sent email?
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * Get error message
     *
     * @return null|string
     */
    public function getErrorMessage()
    {
        return $this->error_msg;
    }

    /**
     * Send the e-mail
     *
     * @return $this
     */
    public function send()
    {
        $ret = false;

        try {
            $ret = $this->mail->send();
            $this->success = true;
        } catch(Exception $e) {
            Charm::Logging()->error('Could not send email', [$e->getMessage(), $this->mail->ErrorInfo]);
            $this->success = false;
            $this->error_msg = $e->getMessage();
        }

        if($ret === false) {
            $this->success = false;

            if(Charm::has('Events')) {
                Charm::Event()->fire('Mailman', 'sentSuccess');
            }
        } else {
            if(Charm::has('Events')) {
                Charm::Event()->fire('Mailman', 'sentError');
            }
        }

        // Clear all recipients to prevent errors with multiple recipients on mass mails
        $this->mail->clearAllRecipients();

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
     * Enable debug outputting of SMTP communication to default log file
     *
     * @return $this
     */
    public function enableDebugOutput()
    {
        $this->mail->SMTPDebug = 4;
        $this->mail->Debugoutput = function($str, $level) {
            Charm::Logging()->info('[MAIL] SMTP ' . $level . ': ' . $str);
        };
        return $this;
    }

    /**
     * Set a manual SMTP connection
     *
     * This can be used instead of using the connections.yaml with setConnection()
     *
     * The parameters are the same as in the config file.
     *
     * @param string $host       host name
     * @param string $username   login user name
     * @param string $password   login password
     * @param string $from_name  from name
     * @param string $from_mail  from email address
     * @param int    $port       (opt.) host port (Default: 587)
     * @param bool   $use_tls    (opt.) use tls? (Default: true)
     * @param bool   $use_ssl    (opt.) use ssl? (Default: false)
     * @param bool   $auth       (opt.) needs auth? (Default: true)
     * @param string $auth_type  (opt.) auth type (Default: LOGIN)
     * @param bool   $trust_all  (opt.) trust all certificates? (Default: false)
     *
     * @return $this
     */
    public function setSmtpConnection(
        $host,
        $username,
        $password,
        $from_name,
        $from_mail,
        $port = 587,
        $use_tls = true,
        $use_ssl = false,
        $auth = true,
        $auth_type = 'login',
        $trust_all = false
    ){
        // Init PHPMailer and set config values
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->XMailer = 'Charm';

            // SMTP connection
            $mail->isSMTP();

            $mail->SMTPAuth = $auth;
            $mail->AuthType = strtoupper($auth_type);
            $mail->Host = $host;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->Port = $port;

            // TLS / SSL security
            if ($use_tls) {
                $mail->SMTPSecure = 'tls';
            } elseif ($use_ssl) {
                $mail->SMTPSecure = 'ssl';
            } else {
                $mail->SMTPSecure = false;
            }

            // Allow self signed certificates
            if ($trust_all) {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            }

            $mail->setFrom(
                $from_mail,
                $from_name
            );
            $this->from = $from_name
                . ' <' .  $from_mail . '>';
        } catch(Exception $e) {
            Charm::Logging()->error('Could not set SMTP connection', [$e->getMessage(), $mail->ErrorInfo]);
        }

        $this->mail = $mail;

        // Debug mode
        if(Charm::Config()->get('main:debug.debugmode', false)) {
            $this->enableDebugOutput();
        }

        return $this;
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

            $type = Charm::Config()->get($configspace . '.type', 'smtp');

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
            $this->from = Charm::Config()->get($configspace . '.fromname')
                . ' <' .  Charm::Config()->get($configspace . '.frommail') . '>';
        } catch(Exception $e) {
            Charm::Logging()->error('Could not set SMTP connection', [$e->getMessage(), $mail->ErrorInfo]);
        }

        $this->mail = $mail;

        // Debug mode
        if(Charm::Config()->get('main:debug.debugmode', false)) {
            $this->enableDebugOutput();
        }

        return $this;
    }
}