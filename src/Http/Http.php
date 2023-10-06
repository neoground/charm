<?php
/**
 * This file contains the Http class
 */

namespace Charm\Http;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use GuzzleHttp\Client;

/**
 * Class Http
 *
 * Module binding to Charm kernel
 *
 * @package Charm\Storage
 */
class Http extends Module implements ModuleInterface
{
    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // Nothing to do on init yet...
    }

    public function request(string $type, string $url, array $options = [], array $clientconfig = []): HttpResponse
    {
        return new HttpResponse(($this->getNewClient($clientconfig))
            ->request(strtoupper($type), $url, $options));
    }

    public function get(string $url, array $options = []): HttpResponse
    {
        return $this->request('GET', $url, $options);
    }

    public function delete(string $url, array $options = []): HttpResponse
    {
        return $this->request('DELETE', $url, $options);
    }

    public function head(string $url, array $options = []): HttpResponse
    {
        return $this->request('HEAD', $url, $options);
    }

    public function options(string $url, array $options = []): HttpResponse
    {
        return $this->request('OPTIONS', $url, $options);
    }

    public function patch(string $url, array $options = []): HttpResponse
    {
        return $this->request('PATCH', $url, $options);
    }

    public function post(string $url, array $options = []): HttpResponse
    {
        return $this->request('POST', $url, $options);
    }

    public function put(string $url, array $options = []): HttpResponse
    {
        return $this->request('PUT', $url, $options);
    }

    public function getNewClient(array $config = []): Client
    {
        return new Client($config);
    }

}