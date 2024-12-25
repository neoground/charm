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
use Predis\Client;

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
     * Get the logged-in user
     *
     * If a user is not logged in, this will return the default user.
     *
     * @param bool $use_cache cache the user object? Default: true
     *
     * @return object|false  the user object or false if guard is disabled
     */
    public function getUser(bool $use_cache = true)
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
        if($use_cache) {
            return $this->user_class::findWithCache($_SESSION['user']);
        }

        return $this->user_class::find($_SESSION['user']);
    }

    /**
     * Get user id
     *
     * This is more performant than getUser() because the id is stored in the session.
     * No database query needed.
     * If a user is not logged in, this will return the default user ID.
     *
     * @return int|false the user id or false if guard is disabled
     */
    public function getUserId(): bool|int
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
    public function isLoggedIn(): bool
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
    private function isExpired(): bool
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
     * Check if the password is correct
     *
     * @param object|string $username the username or user object
     * @param string        $password the password
     *
     * @return bool true if password is correct, false otherwise
     */
    public function checkPassword(object|string $username, string $password): bool
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
     * This will check for the password and call handleLogin().
     * If you need a custom login handling (e.g. TFA), call these
     * methods manually in your Auth controller.
     *
     * @param string $username   the username
     * @param string $password   the password
     * @param bool   $rememberme (opt.) remember user? default: false
     *
     * @return bool
     */
    public function login(string $username, string $password, bool $rememberme = false): bool
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
     * @param bool $auto_logout (opt.) triggered as auto logout? default: false
     */
    public function logout(bool $auto_logout = false): void
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
     * This will log in this user. This won't check the password or anything!
     *
     * @param object  $u           the user object
     * @param bool    $rememberme  remember user?
     *
     * @return bool
     */
    public function handleLogin(object $u, bool $rememberme): bool
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

        // Clear login attempts
        $hashkey = C::Config()->get('main:session.name', 'charm');
        $r = C::Redis()->getClient();
        $ip = C::Request()->getIpAddress();
        if ($r && $ip) {
            try {
                $iphash = md5($ip);
                $r->del($hashkey . ':loginattempts:' . $iphash);
                $r->hdel($hashkey . ':loginattempts:count', $iphash);
            } catch(\Exception $e) { }
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
     * Get amount of wrong login attempts for the current client
     *
     * @return int the amount of wrong login attempts
     */
    public function getWrongLoginAttempts(): int
    {
        $hashkey = C::Config()->get('main:session.name', 'charm');
        $expireTime = ((int) C::Config()->get('main:guard.login_attempts_expiration', 1440)) * 60;

        // Get redis connection
        $r = C::Redis()->getClient();

        // Get ip
        $ip = C::Request()->getIpAddress();

        if ($r && $ip) {
            try {
                $iphash = md5($ip);
                $currentTime = time();

                // Remove expired attempts
                $r->zremrangebyscore($hashkey . ':loginattempts:' . $iphash, '-inf', $currentTime - $expireTime);

                // Get the count of attempts
                $counter = $r->zcard($hashkey . ':loginattempts:' . $iphash);

                // Store the count of attempts
                $r->hset($hashkey . ':loginattempts:count', $iphash, $counter);

                return (int) $counter;
            } catch(\Exception $e) {
                return 0;
            }
        }

        return 0;
    }

    /**
     * Save failed login attempt of the current client in redis
     *
     * @return bool true if saved successfully, false on error
     */
    public function saveWrongLoginAttempt(): bool
    {
        $hashkey = C::Config()->get('main:session.name', 'charm');
        $expireTime = ((int) C::Config()->get('main:guard.login_attempts_expiration', 1440)) * 60;

        // Get redis connection
        $r = C::Redis()->getClient();

        // Get ip
        $ip = C::Request()->getIpAddress();

        if ($r && $ip) {
            try {
                $iphash = md5($ip);

                // Use a sorted set to store the timestamp of each login attempt
                $currentTime = time();
                if($r instanceof Client) {
                    // Predis wants an array
                    $r->zadd($hashkey . ':loginattempts:' . $iphash, [$currentTime, $currentTime]);
                } else {
                    // PHPredis is fine with multiple arguments
                    $r->zadd($hashkey . ':loginattempts:' . $iphash, $currentTime, $currentTime);
                }

                // Remove expired attempts
                $r->zremrangebyscore($hashkey . ':loginattempts:' . $iphash, '-inf', $currentTime - $expireTime);

                // Get the count of attempts
                $counter = $r->zcard($hashkey . ':loginattempts:' . $iphash);

                // Store the count of attempts
                $r->hset($hashkey . ':loginattempts:count', $iphash, $counter);

                return true;
            } catch(\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Hash a password
     *
     * @param string $password
     *
     * @return string
     */
    public function hashPassword(string $password) : string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Throttle the login
     *
     * If a client tried to log in too many times,
     * the login will be throttled. You can configure
     * this in the main:guard.throttle_* config.
     *
     * @return bool true if throttled, false if not
     */
    public function throttleLogin(): bool
    {
        $hashkey = C::Config()->get('main:session.name', 'charm');
        $r = C::Redis()->getClient();
        $ip = C::Request()->getIpAddress();
        $throttleThreshold = (int) C::Config()->get('main:guard.throttle_threshold', 5);
        $throttleDelay = (int) C::Config()->get('main:guard.throttle_seconds', 10);

        if ($r && $ip) {
            $iphash = md5($ip);

            // Check the count of attempts
            try {
            $counter = $r->hget($hashkey . ':loginattempts:count', $iphash);
            } catch (\Exception $e) {
                return false;
            }

            if ($counter && is_numeric($counter)) {
                if ($counter >= $throttleThreshold) {
                    // Apply throttling delay
                    sleep($throttleDelay);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if the client is blocked (too many login attempts)
     *
     * This should be called on top of your Auth handler and
     * abort the login process asap if the client is blocked.
     *
     * @return bool true if blocked, false if not
     */
    public function isBlocked(): bool
    {
        $hashkey = C::Config()->get('main:session.name', 'charm');
        $maxAttempts = (int) C::Config()->get('main:guard.max_login_attempts', 20);
        $r = C::Redis()->getClient();
        $ip = C::Request()->getIpAddress();

        if ($r && $ip) {
            $iphash = md5($ip);

            // Check the count of attempts
            try {
                $counter = $r->hget($hashkey . ':loginattempts:count', $iphash);
                if ($counter && is_numeric($counter)) {
                    return $counter >= $maxAttempts;
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

}