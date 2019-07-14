<?php
/**
 * This file contains the Token class
 */

namespace Charm\Guard;

use Carbon\Carbon;
use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Token
 *
 * Module binding to Charm kernel
 *
 * @package Charm\Guard
 */
class Token extends Module implements ModuleInterface
{
    /** @var string  the token */
    protected $token;

    /** @var string  the token location */
    protected $token_location;

    /** @var string  the client token */
    protected $client_token;

    /** @var string  the user class */
    protected $user_class;

    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // Get user class
        $this->user_class = Charm::Config()->get('main:guard.user_class', 'App\\Models\\User');
        $this->token_location = Charm::Config()->get('main:guard.token_location', 'api_token');

        // Get token
        $this->getToken();
    }

    /**
     * Get the token
     *
     * @return bool|string  the token / false if no token is found
     */
    public function getToken()
    {
        $auth_header = Charm::Request()->getHeader('authorization');

        $matches = [];
        preg_match('/usertoken="(.*?)"/', $auth_header, $matches);
        if(isset($matches[1])){
            $token = $matches[1];
            $this->token = $token;
            return $token;
        }

        return false;
    }

    /**
     * Check if a token is provided
     *
     * @return bool
     */
    public function hasToken()
    {
        return !empty($this->token);
    }

    /**
     * Get the client token
     *
     * @return bool|string  the token / false if no token is found
     */
    public function getClientToken()
    {
        $auth_header = Charm::Request()->getHeader('authorization');

        $matches = [];
        preg_match('/client="(.*?)"/', $auth_header, $matches);
        if(isset($matches[1])){
            $token = $matches[1];
            $this->client_token = $token;
            return $token;
        }

        return false;
    }

    /**
     * Check if an app token is provided
     *
     * @return bool
     */
    public function hasClientToken()
    {
        return !empty($this->client_token);
    }

    /**
     * Find user by current token
     *
     * @return mixed user object or false if none found
     */
    private function findUserByToken()
    {
        if(class_exists($this->token_location)) {
            // Got class
            $token_class = $this->token_location::where('token', $this->getToken())
                ->where('expiration', '>=', Carbon::now()->toDateTimeString())
                ->where('type', 'api')
                ->first();

            if(is_object($token_class)) {
                return $this->user_class::findWithCache($token_class->user_id);
            }

            return false;
        }

        // Got field
        return $this->user_class::where($this->token_location, $this->token)->first();
    }

    /**
     * Get the user by the provided token
     *
     * @return object  the user object  (if no user is found, the system user will be returned)
     */
    public function getUser()
    {
        // Get user by token stored in redis
        if(Charm::has('Redis')) {
            $user_id = Charm::Redis()->getClient()->hget('api_user', $this->token);
            if(!empty($user_id)) {
                $user = $this->user_class::findWithCache($user_id);
                if(is_object($user)) {
                    return $user;
                }
            }
        }

        $u = $this->findUserByToken();

        // If user not found -> use system user
        $default_user = false;
        if (!is_object($u)) {
            $u = $this->user_class::getDefaultUser();
            $default_user = true;
        }

        // Store in redis cache
        if(Charm::has('Redis') && !$default_user) {
            Charm::Redis()->getClient()->hset('api_user', $this->token, $u->id);
        }

        return $u;
    }

    /**
     * Check the api authentication
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        if(!empty($this->token)) {
            // Check redis
            $in_redis = Charm::has('Redis') && Charm::Redis()->getClient()->hexists('api_user', $this->token);

            // Check database if not in redis yet
            return $in_redis || is_object($this->findUserByToken());
        }

        return false;
    }

    /**
     * Generate a token
     *
     * The random bytes will be base64 encoded (without special characters).
     * So a 48 byte long input will create a 63 characters token.
     *
     * @param int  $bytes    bytes length, default 16
     * @param bool $apitoken (opt.) is it an api token which must be unique in api_token column? Default: false
     *
     * @return string
     */
    public function createToken($bytes = 16, $apitoken = false)
    {
        $token = base64_encode(openssl_random_pseudo_bytes($bytes));
        $token = str_replace(['+', '/', '='], "", $token);

        // Check if token in database. If so, generate new one!
        if($apitoken) {
            while ($this->user_class::where('api_token', $token)->count() > 0) {
                $token = base64_encode(openssl_random_pseudo_bytes($bytes));
                $token = str_replace(['+', '/', '='], "", $token);
            }
        }

        return $token;
    }


}