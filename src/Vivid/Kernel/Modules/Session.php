<?php
/**
 * This file contains the init class for the session.
 */

namespace Charm\Vivid\Kernel\Modules;

use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Session
 *
 * Session module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Session extends Module implements ModuleInterface
{
    /**
     * Load the module
     */
    public function loadModule()
    {
        // Set lifetime and session data
        ini_set('session.gc_maxlifetime', C::Config()->get('main:session.expire', 720) * 60);
        ini_set('session.gc_divisor', 1);
        ini_set('session.gc_probability', 0);

        // Session cookies
        ini_set('session.cookie_lifetime', 0);
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);

        // Set session name
        session_name(C::Config()->get('main:session.name', 'charm'));

        // Start session
        session_start();

        // Check fingerprint on every page load
        if(!$this->checkFingerprint()) {
            // Invalid fingerprint.
            // Destroy session!
            $this->destroy();
        }
    }

    /**
     * Destroy current session
     *
     * @return bool
     */
    public function destroy() : bool
    {
        // Return false if we don't have a session
        if (session_status() != PHP_SESSION_ACTIVE) {
            return false;
        }

        // Empty and destroy session
        $_SESSION = [];
        session_destroy();

        // Create fresh empty session
        return session_start();
    }

    /**
     * Refresh current session
     *
     * @return bool
     */
    public function refresh() : bool
    {
        return session_regenerate_id(true);
    }

    /**
     * Get a session value
     *
     * @param string  $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $val = C::Arrays()->get($_SESSION, $key, null);

        if(empty($val)) {
            return null;
        }

        return from_string($val);
    }

    /**
     * Check if the session contains this key
     *
     * @param string  $key
     *
     * @return bool
     */
    public function has($key) : bool
    {
        return C::Arrays()->has($_SESSION, $key);
    }

    /**
     * Set a session value
     *
     * @param string  $key
     * @param mixed   $value
     */
    public function set($key, $value)
    {
        // We need a string as value
        $value = to_string($value);
        $_SESSION[$key] = $value;
    }

    /**
     * Delete a session value
     *
     * @param $key
     */
    public function delete($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Validate fingerprint of browser
     *
     * This will prevent some session cookie hijacking
     *
     * @return bool
     */
    public function checkFingerprint() : bool
    {
        if(isset($_SERVER['HTTP_USER_AGENT'])) {
            $hash = md5($_SERVER['HTTP_USER_AGENT']);

            if (isset($_SESSION['charm_fingerprint'])) {
                return $_SESSION['charm_fingerprint'] === $hash;
            }

            $_SESSION['charm_fingerprint'] = $hash;
        }

        return true;
    }

    /**
     * Return all session data
     *
     * @return array
     */
    public function all() : array
    {
        return $_SESSION;
    }

}