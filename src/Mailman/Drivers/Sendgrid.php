<?php
/**
 * This file contains the mailman Sendgrid driver
 */

namespace Charm\Mailman\Drivers;

use Charm\Mailman\MailmanDriverInterface;
use Charm\Vivid\C;
use SendGrid\Mail\From;
use SendGrid\Mail\Mail;

/**
 * Class Sendgrid
 *
 * Sendgrid driver for Mailman
 *
 * @package Charm\Mailman\Drivers
 */
class Sendgrid implements MailmanDriverInterface
{
    /** @var \SendGrid\Mail\Mail the sendgrid mail instance */
    protected $mail;

    /** @var string the config prefix */
    protected $configspace;

    /** @var string the text content */
    protected $text_content;

    /** @var string the html content */
    protected $html_content;

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
        $x->configspace = 'connections:emails.' . $data;
        $x->mail = new Mail(
            new From(C::Config()->get($x->configspace . '.frommail'),
                C::Config()->get($x->configspace . '.fromname'))
        );

        return $x;
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
        $this->mail->addTo($email, $name);
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
        $this->mail->addCc($email, $name);
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
        $this->mail->addBcc($email, $name);
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
        if (empty($name)) {
            $name = basename($path);
        }

        $attachment = new \SendGrid\Mail\Attachment(
            file_get_contents($path),
            mime_content_type($path),
            $name
        );
        $this->mail->addAttachment($attachment);
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
        $this->mail->addContent("text/plain", $msg);
        $this->text_content = $msg;
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
        $this->mail->addContent("text/html", $msg);
        $this->html_content = $msg;
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
        $this->mail->setSubject($name);
        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->html_content;
    }

    /**
     * Get text body (alt body)
     *
     * @return string
     */
    public function getTextBody()
    {
        return $this->text_content;
    }

    /**
     * Get from string
     *
     * @return \SendGrid\Mail\From
     */
    public function getFrom()
    {
        return $this->mail->getFrom();
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
        $sendgrid = new \SendGrid(C::Config()->get($this->configspace . '.sendgrid.token'));
        try {
            $response = $sendgrid->send($this->mail);
            $this->success = ((int)$response->statusCode() < 300);

            if (!$this->success) {
                C::Logging()->error('[SENDGRID] [Code ' . (int)$response->statusCode() . '] Error: ' . $response->body());
            }

        } catch (\Exception $e) {
            $this->error_msg = $e->getMessage();
            C::Logging()->error('[SENDGRID] Exception: ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * Get sendgrid mail object
     *
     * @return Mail
     */
    public function getMail()
    {
        return $this->mail;
    }
}