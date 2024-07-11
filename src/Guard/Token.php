<?php
/**
 * This file contains the Token class
 */

namespace Charm\Guard;

use Carbon\Carbon;
use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Illuminate\Database\QueryException;

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
    protected string $token;

    /** @var string  the token location */
    protected string $token_location;

    /** @var string  the client token */
    protected string $client_token;

    /** @var string  the user class */
    protected string $user_class;

    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // Get user class
        $this->user_class = C::Config()->get('main:guard.user_class', 'App\\Models\\User');
        $this->token_location = C::Config()->get('main:guard.token_location', 'api_token');

        // Get token
        $this->getToken();
    }

    /**
     * Get the token
     *
     * @return bool|string  the token / false if no token is found
     */
    public function getToken(): bool|string
    {
        if (!empty($this->token)) {
            return $this->token;
        }

        $auth_header = C::Header()->get('authorization');

        if (empty($auth_header)) {
            // Try x-authorization, some prefer this
            $auth_header = C::Header()->get('x-authorization');
        }

        if (!empty($auth_header)) {
            $matches = [];
            preg_match('/usertoken="(.*?)"/', $auth_header, $matches);
            if (isset($matches[1])) {
                $token = $matches[1];
                $this->token = $token;
                return $token;
            }

            // Second try: Bearer token
            if (str_starts_with($auth_header, 'Bearer')) {
                $parts = explode("Bearer", $auth_header);
                return trim($parts[1]);
            }
        }

        return false;
    }

    /**
     * Check if a token is provided
     *
     * @return bool
     */
    public function hasToken(): bool
    {
        return !empty($this->token);
    }

    /**
     * Get the client token
     *
     * @return bool|string  the token / false if no token is found
     */
    public function getClientToken(): bool|string
    {
        $auth_header = C::Header()->get('authorization');

        $matches = [];
        preg_match('/client="(.*?)"/', $auth_header, $matches);
        if (isset($matches[1])) {
            $token = $matches[1];
            $this->client_token = $token;
            return $token;
        }

        return false;
    }

    /**
     * Check if an client (app) token is provided
     *
     * @return bool
     */
    public function hasClientToken(): bool
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
        if (class_exists($this->token_location)) {
            // Got class
            $token_class = $this->token_location::where('token', $this->getToken())
                ->where('expiration', '>=', Carbon::now()->toDateTimeString())
                ->where('type', 'api')
                ->first();

            if (is_object($token_class)) {
                return $this->user_class::find($token_class->user_id);
            }

            return false;
        }

        // Got field
        try {
            return $this->user_class::where($this->token_location, $this->token)->first();
        } catch (QueryException $e) {
            return false;
        }
    }

    /**
     * Get the user by the provided token
     *
     * @return object  the user object  (if no user is found, the system user will be returned)
     */
    public function getUser()
    {
        // Get user by token stored in redis
        if (C::has('Redis')) {
            $user_id = C::Redis()->getClient()->hget('api_user', $this->token);
            if (!empty($user_id)) {
                $user = $this->user_class::find($user_id);
                if (is_object($user)) {
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
        if (C::has('Redis') && !$default_user) {
            C::Redis()->getClient()->hset('api_user', $this->token, $u->id);
        }

        return $u;
    }

    /**
     * Check the api authentication
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if (!empty($this->token)) {
            // Check redis
            $in_redis = C::has('Redis') && C::Redis()->getClient()->hexists('api_user', $this->token);

            // Check database if not in redis yet
            return $in_redis || is_object($this->findUserByToken());
        }

        return false;
    }

    /**
     * Generate a secure token
     *
     * @param int $length The length of the token (default = 16)
     *
     * @return string The generated token
     */
    public function generateSecureToken(int $length = 16): string
    {
        $keyspace = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz123456789';
        $str = '';
        $max = strlen($keyspace) - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    /**
     * Generate a token
     *
     * The token consists of A-Z, a-z, 1-9, without characters which can be
     * misunderstood like 1, I, l, o, 0
     *
     * @param int  $length   token length, default 16
     * @param bool $apitoken (opt.) is it an api token which must be unique in api_token column? Default: false
     *
     * @return string
     */
    public function createToken(int $length = 16, bool $apitoken = false): string
    {
        $token = $this->generateSecureToken($length);
        $token = str_replace(['+', '/', '='], "", $token);

        // Check if token in database. If so, generate new one!
        if ($apitoken) {
            if (class_exists($this->token_location)) {
                $this->token_location::getUniqueToken('api');
            } else {
                // Find in user table
                while ($this->user_class::where($this->token_location, $token)->count() > 0) {
                    $token = $this->generateSecureToken($length);
                    $token = str_replace(['+', '/', '='], "", $token);
                }
            }
        }

        return $token;
    }

    /**
     * Manually set token
     *
     * Please note: This will change the token from every code executed after this command.
     *              This will not change the detected token in the charm init procedure.
     *
     * @param int|string $token new token
     *
     * @return $this
     */
    public function setToken(int|string $token): static
    {
        $this->token = $token;

        return $this;
    }


}