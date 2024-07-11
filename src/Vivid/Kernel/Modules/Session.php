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
        if (!$this->checkFingerprint()) {
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
    public function destroy(): bool
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
    public function refresh(): bool
    {
        return session_regenerate_id(true);
    }

    /**
     * Get a session value
     *
     * @param string     $key
     * @param mixed|null $default default value to return if $key is not found
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $val = C::Arrays()->get($_SESSION, $key, null);
        return ($val === null) ? $default : from_string($val);
    }

    /**
     * Check if the session contains this key
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return C::Arrays()->has($_SESSION, $key);
    }

    /**
     * Set a session value
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set(string $key, mixed $value)
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
    public function checkFingerprint(): bool
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
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
    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * Save and close the current session
     *
     * This is done when the script exeuction ends.
     * But you can also call it when you don't need to write to the session anymore,
     * so you can prevent session locking. Reading is of course still possible after that.
     *
     * @return bool Returns true on success and false on failure.
     */
    public function saveAndClose(): bool
    {
        return session_write_close();
    }

    /**
     * Generate a unique CSRF token and store it in the session
     *
     * @param bool $force_new Force the creation of a new token if it already exists. Default: false
     *
     * @return string the token
     */
    public function generateCsrfToken(bool $force_new = false): string
    {
        $token = $this->get('csrf_token', false);
        if (empty($token) || $force_new) {
            $token = C::Token()->generateSecureToken(32);
            $this->set('csrf_token', $token);
        }
        return $token;
    }

    /**
     * Validate a CSRF token
     *
     * @param string $token the provided token
     *
     * @return bool
     */
    public function validateCsrfToken(string $token): bool
    {
        $session_token = $this->get('csrf_token', false);
        return !empty($session_token) && hash_equals($session_token, $token);
    }

}