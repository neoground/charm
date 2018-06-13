<?php
/**
 * This file contains the Token class
 */

namespace Charm\Guard;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Charm;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\Kernel\Traits\SingletonTrait;

/**
 * Class Token
 *
 * Module binding to Charm kernel
 *
 * @package Charm\Guard
 */
class Token extends Module implements ModuleInterface
{
    use SingletonTrait;

    /** @var string  the token */
    protected $token;

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
        $this->user_class = Charm::App()->getConfig('user_class');

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
        $auth_header = Charm::Request()->getHeader('Authorization');

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
        $auth_header = Charm::Request()->getHeader('Authorization');

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
     * Get the user by the provided token
     *
     * @return object  the user object  (if no user is found, the system user will be returned)
     */
    public function getUser()
    {
        $u = $this->user_class::where('api_token', $this->token)->first();

        // If user not found -> use system user
        if (!is_object($u)) {
            $u = $this->user_class::getDefaultUser();
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
        return !empty($this->token) && $this->user_class::where('api_token', $this->token)->count() > 0;
    }

    /**
     * Generate a token
     *
     * The random bytes will be base64 encoded (without special characters).
     * So a 48 byte long input will create a 63 characters token.
     *
     * @param int  $bytes  bytes length, default 16
     *
     * @return string
     */
    public function createToken($bytes = 16)
    {
        $token = base64_encode(openssl_random_pseudo_bytes($bytes));
        $token = str_replace(['+', '/', '='], "", $token);

        // Check if token in database. If so, generate new one!
        while ($this->user_class::where('api_token', $token)->count() > 0) {
            $token = base64_encode(openssl_random_pseudo_bytes($bytes));
            $token = str_replace(['+', '/', '='], "", $token);
        }

        return $token;
    }


}