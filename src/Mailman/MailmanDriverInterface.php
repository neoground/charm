<?php
/**
 * This file contains the RouterElement interface
 */

namespace Charm\Mailman;

/**
 * Mailman Driver Interface
 *
 * @package Charm\Mailman
 */
interface MailmanDriverInterface
{
    /**
     * Driver factory
     *
     * @param null|mixed $data optional data to pass to driver
     *
     * @return self
     */
    public static function compose($data = null);

    /**
     * Add recipient
     *
     * @param string  $email  the e-mail address
     * @param string  $name   (opt.) recipient name
     *
     * @return $this
     */
    public function addAddress($email, $name = '');

    /**
     * Add a CC address
     *
     * @param string  $email  the e-mail address
     * @param string  $name   (opt.) recipient name
     *
     * @return $this
     */
    public function addCC($email, $name = '');

    /**
     * Add a BCC address
     *
     * @param string  $email  the e-mail address
     * @param string  $name   (opt.) recipient name
     *
     * @return $this
     */
    public function addBCC($email, $name = '');

    /**
     * Add an attachment
     *
     * @param string  $path  absolute path to file
     * @param string  $name  (opt.) filename of attachment
     *
     * @return $this
     */
    public function addAttachment($path, $name = '');

    /**
     * Set plain text content of message
     *
     * @param string  $msg  the message
     *
     * @return $this
     */
    public function setTextContent($msg);

    /**
     * Set HTML content of message
     *
     * @param string  $msg  the message
     *
     * @return $this
     */
    public function setHtmlContent($msg);

    /**
     * Set e-mail subject
     *
     * @param string  $name  the subject
     *
     * @return $this
     */
    public function setSubject($name);

    /**
     * Get body
     *
     * @return string
     */
    public function getBody();

    /**
     * Get text body (alt body)
     *
     * @return string
     */
    public function getTextBody();

    /**
     * Get from string
     *
     * @return string
     */
    public function getFrom();

    /**
     * Successfully sent email?
     *
     * @return bool
     */
    public function isSuccess();

    /**
     * Get error message
     *
     * @return null|string
     */
    public function getErrorMessage();

    /**
     * Send the e-mail
     *
     * @return $this
     */
    public function send();
}