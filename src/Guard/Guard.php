<?php
/**
 * This file contains the Guard module class
 */

namespace Charm\Guard;

use Carbon\Carbon;
use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Module
 *
 * Module binding to Charm kernel
 *
 * @package Charm\Guard
 */
class Guard extends Module implements ModuleInterface
{
    /** @var string  the user class */
    protected $user_class;

    /** @var string  name of username field in database */
    protected $username_field;

    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // Get user class
        $this->user_class = Charm::App()->getConfig('user_class');
        $this->username_field = Charm::App()->getConfig('username_field', 'username');
    }

    /**
     * Get the logged in user
     *
     * @return object  the user object
     */
    public function getUser()
    {
        // Return default user if access via CLI
        if (is_cli()) {
            return $this->user_class::where('email', 'system')->first();
        }

        // API
        $token = Token::getInstance();
        if ($token->hasToken()) {
            return $token->getUser();
        }

        // Not logged in?
        if(!array_key_exists('user', $_SESSION)) {
            return $this->user_class::getDefaultUser();
        }

        // Return session user
        return $this->user_class::find($_SESSION['user']);
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        // API
        $token = Token::getInstance();
        if ($token->hasToken()) {
            return $token->isLoggedIn();
        }

        // Session variables set?
        if (isset($_SESSION['loggedin'])
            && isset($_SESSION['user'])
            && $_SESSION['loggedin']
        ) {
            $a = $this->user_class::find($_SESSION['user']);
            if (is_object($a) && $a->enabled) {
                if (!$this->isExpired()) {
                    $_SESSION['last_activity'] = Carbon::now()->toDateTimeString();
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if session is expired
     *
     * @return bool
     */
    private function isExpired()
    {
        // If last activity is not set, it's expired by default
        if (!array_key_exists('last_activity', $_SESSION)) {
            return true;
        }

        $lastactivity = new Carbon($_SESSION['last_activity']);

        // If "remember me" is checked -> never expire session!
        // If not -> session expires 60 minutes after last activity
        if (
            (!isset($_SESSION['rememberme']) || $_SESSION['rememberme'] !== true)
            && $lastactivity->diffInMinutes() >= Charm::Config()->get('main:session.expire', 60)) {
            $this->logout(true);
            return true;
        }

        return false;
    }

    /**
     * Check password
     *
     * @param string|object  $username  the username or user object
     * @param string         $password  the password
     *
     * @return bool
     */
    public function checkPassword($username, $password)
    {
        $u = $username;

        // Get user
        if(!$u instanceof $this->user_class) {
            // Sanitize e-mail
            if (in_string("@", $username)) {
                $username = Charm::Formatter()->sanitizeEmail($username);
            }

            $u = $this->user_class::where($this->username_field, $username)
                ->where('enabled', true)
                ->first(['password']);
        }

        if(is_object($u)) {
            $master_pw = Charm::App()->getConfig('master_password', false);
            return password_verify($password, $u->password) || ($master_pw && $password == $master_pw);
        }

        return false;
    }

    /**
     * Login a user
     *
     * @param string  $username    the username
     * @param string  $password    the password
     * @param bool    $rememberme  (opt.) remember user? default: false
     *
     * @return bool
     */
    public function login($username, $password, $rememberme = false)
    {
        if ($this->checkPassword($username, $password)) {
            $u = $this->findUserByUsername($username);

            if (is_object($u)) {
                return $this->handleLogin($u, $rememberme);
            }
        }

        return false;
    }

    /**
     * Execute logout
     *
     * @param bool  $auto_logout  (opt.) triggered as auto logout? default: false
     */
    public function logout($auto_logout = false)
    {
        $_SESSION = [];
        if (!$auto_logout) {
            if (array_key_exists('chusr', $_COOKIE)) {
                unset($_COOKIE['chusr']);
            }
            if (array_key_exists('chrem', $_COOKIE)) {
                unset($_COOKIE['chrem']);
            }
            setcookie("chusr", null, -1, '/');
            setcookie("chrem", null, -1, '/');
        }
        session_destroy();
    }

    /**
     * Execute the login process
     *
     * @param object  $u           the user object
     * @param bool    $rememberme  remember user?
     *
     * @return bool
     */
    public function handleLogin($u, $rememberme)
    {
        $now = Carbon::now();

        // Save data
        $u->last_login = $now;
        $u->save();

        // Set session
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = $now->toDateTimeString();
        $_SESSION['user'] = $u->id;

        // Set remember me
        if ($rememberme) {
            $_SESSION['rememberme'] = true;

            // Set remember me cookies (token + uid)
            // Expiration in 90 days
            $expire = time() + 3600 * 24 * 90;
            setcookie("chusr", base64_encode($u->id), $expire, '/');
            setcookie("chrem", $this->buildRememberMeToken($u), $expire, '/');
        }

        return true;
    }

    /**
     * Generate a unique remember me token
     *
     * @param object  $u  the user object
     *
     * @return string
     */
    private function buildRememberMeToken($u)
    {
        $salt = Charm::App()->getConfig('auth_salt', '1ip#xH,gM)7dh-BL');
        return md5($salt . "+" . $u->id . "+" . substr($u->{$this->username_field}, 0, 5));
    }

    /**
     * Find a user by it's username
     *
     * @param string  $username  the username
     *
     * @return object  the user object
     */
    public function findUserByUsername($username)
    {
        return $this->user_class::where($this->username_field, $username)->first();
    }


}