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
 * Class Guard
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
        $this->user_class = Charm::Config()->get('main:guard.user_class');
        $this->username_field = Charm::Config()->get('main:guard.username_field', 'username');

        // Auto login user if cookies are present
        $this->doAutoLogin();
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
            return $this->user_class::getDefaultUser();
        }

        // API
        if (Charm::Token()->hasToken()) {
            return Charm::Token()->getUser();
        }

        // Not logged in?
        if(!array_key_exists('user', $_SESSION)) {
            return $this->user_class::getDefaultUser();
        }

        // Return session user
        return $this->user_class::findWithCache($_SESSION['user']);
    }

    /**
     * Get user id
     *
     * This is more performant than getUser() because the id is stored in the session.
     * No database query needed.
     *
     * @return int
     */
    public function getUserId()
    {
        // API calls (with token) can have a different user on each request
        // So always get the user by token to prevent problems
        if (Charm::Token()->hasToken()) {
            return Charm::Token()->getUser()->id;
        }

        if(empty($_SESSION['user'])) {
            // No user set yet. Get from database, save it for better performance the next time
            $u = $this->getUser();
            if(!empty($u->id) && $u->id !== $this->user_class::getDefaultUser()) {
                $_SESSION['user'] = $u->id;
                return $u->id;
            }
        }

        return $_SESSION['user'];
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        // API
        if (Charm::Token()->hasToken()) {
            return Charm::Token()->isLoggedIn();
        }

        // Session variables set?
        if (isset($_SESSION['logged_in'])
            && isset($_SESSION['user'])
            && $_SESSION['logged_in']
        ) {
            $a = $this->user_class::findWithCache($_SESSION['user']);
            if (is_object($a) && $a->enabled && $a->id !== $this->user_class::getDefaultUser()->id) {
                if (!$this->isExpired()) {
                    $_SESSION['last_activity'] = Carbon::now()->toDateTimeString();
                    return true;
                }
            }
        }

        // Remember me cookie

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
            $master_pw = Charm::Config()->get('main:guard.master_password', false);
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
        $session = Charm::Config()->get('main:session.name');
        if (!$auto_logout) {
            if (array_key_exists($session . 'chusr', $_COOKIE)) {
                unset($_COOKIE[$session . 'chusr']);
            }
            if (array_key_exists($session . 'chrem', $_COOKIE)) {
                unset($_COOKIE[$session . 'chrem']);
            }
            setcookie($session . "chusr", null, -1, '/');
            setcookie($session . "chrem", null, -1, '/');
        }
        session_destroy();

        // Set session name + start a clean session
        // So messages etc. work as expected
        session_name(Charm::Config()->get('main:session.name', 'charm'));
        session_start();

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
            $session = Charm::Config()->get('main:session.name');
            setcookie(
                $session . "chusr", base64_encode($u->id), $expire, '/'
            );
            setcookie(
                $session . "chrem", $this->buildRememberMeToken($u), $expire, '/'
            );
        }

        return true;
    }

    /**
     * Automatically log in user
     *
     * If user sets "remember me" a cookie is set for a period of time.
     * If we find the cookie with valid data, the user gets logged in.
     */
    public function doAutoLogin()
    {
        $session = Charm::Config()->get('main:session.name');

        if (!$this->isLoggedIn()) {
            if (array_key_exists($session . 'chrem', $_COOKIE) && array_key_exists($session . 'chusr', $_COOKIE)) {
                // Find user by id
                $uid = base64_decode($_COOKIE[$session . 'chusr']);
                $u = $this->user_class::findWithCache($uid);
                if (is_object($u)) {
                    // Check token (cookie password)
                    if ($this->buildRememberMeToken($u) == $_COOKIE[$session . 'chrem']) {
                        // Token is valid -> do the login
                        $this->handleLogin($u, true);
                    }
                }
            }
        }
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
        $salt = Charm::Config()->get('main:guard.auth_salt', '1ip#xH,gM)7dh-BL');
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

    /**
     * Get amount of wrong login attempts for current IP
     *
     * Note: Make sure that you remove that hash every day or so.
     *       Login attempts don't expire itself!
     *
     * @return int
     */
    public function getWrongLoginAttempts()
    {
        $hashkey = Charm::Config()->get('main:session.name', 'charm');

        // Get redis connection
        $r = Charm::Database()->getRedisClient();

        // Get ip
        $ip = Charm::Request()->getIpAddress();

        if ($ip) {
            $iphash = md5($ip);

            $counter = $r->hget($hashkey . ':loginattempts', $iphash);
            if (!$counter) {
                $counter = 0;
            }

            return $counter;
        }

        return 0;
    }

    /**
     * Save wrong login attempt of current IP in redis
     *
     * Note: Make sure that you remove that hash every day or so.
     *       Login attempts don't expire itself!
     */
    public function saveWrongLoginAttempt()
    {
        $hashkey = Charm::Config()->get('main:session.name', 'charm');

        // Get redis connection
        $r = Charm::Database()->getRedisClient();

        // Get ip
        $ip = Charm::Request()->getIpAddress();

        if ($ip) {
            $iphash = md5($ip);

            $counter = $r->hget($hashkey . ':loginattempts', $iphash);
            if (!$counter) {
                $counter = 0;
            }

            $counter++;

            $r->hset('loginattempts', $iphash, $counter);
        }
    }


}