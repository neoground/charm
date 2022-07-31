<?php
/**
 * This file contains the Guard module class
 */

namespace Charm\Guard;

use Carbon\Carbon;
use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\Kernel\Output\Redirect;
use Charm\Vivid\Router\Elements\Filter;

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
        $this->user_class = C::Config()->get('main:guard.user_class');
        $this->username_field = C::Config()->get('main:guard.username_field', 'username');

        // Auto login user if cookies are present and guard is enabled
        if(C::Config()->get('main:guard.enabled', true)) {
            $this->doAutoLogin();
        }

        // Add route filter
        Filter::add('guard:auth', self::class . "::checkAuth");
    }

    /**
     * Check authentication and if user can access this specific page
     *
     * TODO: Add permissions system
     *
     * @return null|Redirect
     */
    public static function checkAuth()
    {
        if(!C::Guard()->isLoggedIn()) {
            // Got NO valid login -> redirect to no auth route
            return Redirect::to(C::Config()->get('main:guard.no_auth_route', 'no_auth'));
        }

        return null;
    }

    /**
     * Get the logged in user
     *
     * @return object|false  the user object or false if guard is disabled
     */
    public function getUser()
    {
        if(!C::Config()->get('main:guard.enabled', true)) {
            return false;
        }

        // Return default user if access via CLI
        if (is_cli()) {
            return $this->user_class::getDefaultUser();
        }

        // API
        if (C::Token()->hasToken()) {
            return C::Token()->getUser();
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
     * @return int|false the user id or false if guard is disabled
     */
    public function getUserId()
    {
        if(!C::Config()->get('main:guard.enabled', true)) {
            return false;
        }

        // API calls (with token) can have a different user on each request
        // So always get the user by token to prevent problems
        if (C::Token()->hasToken()) {
            return C::Token()->getUser()->id;
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
        if(!C::Config()->get('main:guard.enabled', true)) {
            return false;
        }

        // API
        if (C::Token()->hasToken()) {
            return C::Token()->isLoggedIn();
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

        $lastactivity = Carbon::parse($_SESSION['last_activity']);

        // If "remember me" is checked -> never expire session!
        // If not -> session expires 60 minutes after last activity
        if (
            (!isset($_SESSION['rememberme']) || $_SESSION['rememberme'] !== true)
            && $lastactivity->diffInMinutes() >= C::Config()->get('main:session.expire', 60)) {
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

        if(is_string($u)) {
            $u = $this->findUserByUsername($u);
        }

        // Get user
        if(!$u instanceof $this->user_class) {
            // Sanitize e-mail
            if (str_contains($username, "@")) {
                $username = C::Formatter()->sanitizeEmail($username);
            }

            $u = $this->user_class::where($this->username_field, $username)
                ->where('enabled', true)
                ->first(['password']);
        }

        if(is_object($u)) {
            $master_pw = C::Config()->get('main:guard.master_password', false);
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
        $session = C::Config()->get('main:session.name');
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
        session_name(C::Config()->get('main:session.name', 'charm'));
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
        C::Session()->set('logged_in', true);
        C::Session()->set('last_activity', $now->toDateTimeString());
        C::Session()->set('user', $u->id);

        // Set language if user model provides this feature
        if(method_exists($u, 'getLanguage')) {
            C::Formatter()->setLanguage($u->getLanguage());
        }

        // Set remember me
        if ($rememberme) {
            C::Session()->set('rememberme', true);

            // Set remember me cookies (token + uid)
            // TODO Set random token in user and use this instead of base64 of uid for better security
            // Expiration in 90 days
            $expire = time() + 3600 * 24 * 90;
            $session = C::Config()->get('main:session.name');
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
        $session = C::Config()->get('main:session.name');

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
        $salt = C::Config()->get('main:guard.auth_salt', '1ip#xH,gM)7dh-BL');
        return md5($salt . "+" . $u->id . "+" . substr($u->{$this->username_field}, 0, 5));
    }

    /**
     * Find a user by its username
     *
     * @param string  $username  the username
     *
     * @return object|false  the user object or false if not found
     */
    public function findUserByUsername($username)
    {
        // Empty usernames are not allowed
        $username = trim($username);
        if(empty($username)) {
            return false;
        }

        return $this->user_class::where($this->username_field, 'LIKE', $username)->first();
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
        $hashkey = C::Config()->get('main:session.name', 'charm');

        // Get redis connection
        $r = C::Redis()->getClient();

        // Get ip
        $ip = C::Request()->getIpAddress();

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
        $hashkey = C::Config()->get('main:session.name', 'charm');

        // Get redis connection
        $r = C::Redis()->getClient();

        // Get ip
        $ip = C::Request()->getIpAddress();

        if ($ip) {
            $iphash = md5($ip);

            $counter = $r->hget($hashkey . ':loginattempts', $iphash);
            if (!$counter || !is_numeric($counter)) {
                $counter = 0;
            }

            $counter++;

            $r->hset('loginattempts', $iphash, $counter);
        }
    }


}