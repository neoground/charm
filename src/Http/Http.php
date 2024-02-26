<?php
/**
 * This file contains the Http class
 */

namespace Charm\Http;

use Charm\Vivid\Base\Module;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Http
 *
 * The Http class provides methods for performing HTTP requests.
 */
class Http extends Module implements ModuleInterface
{
    protected array $default_options = [];

    /**
     * Load the module
     *
     * This method is executed when the module is loaded to the kernel
     */
    public function loadModule()
    {
        // Set default options
        $this->default_options = [
            'allow_redirects' => 'true',
        ];
    }

    /**
     * Adds a generic User-Agent header to the default options of the current instance.
     *
     * This sets the User-Agent, Accept and DNT request headers to a common value (Firefox Browser on Windows 10).
     *
     * @return self Returns a new instance of the class with the updated default options.
     */
    public function withGenericUserAgent(): self
    {
        $x = clone $this;
        $x->default_options = [
            ...$this->default_options,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'DNT' => 1,
            ],
        ];

        return $x;
    }

    /**
     * Perform an HTTP request.
     *
     * @param string $type         The HTTP request method (e.g., GET, POST).
     * @param string $url          The URL to send the request to.
     * @param array  $options      Additional options to modify the behavior of the request. Defaults to an empty array.
     * @param array  $clientconfig Additional configuration options for the HTTP client. Defaults to an empty array.
     *
     * @return HttpResponse  The HTTP response received from the request.
     */
    public function request(string $type, string $url, array $options = [], array $clientconfig = []): HttpResponse
    {
        return new HttpResponse(($this->getNewClient($clientconfig))
            ->request(strtoupper($type), $url, [...$this->default_options, ...$options]));
    }

    /**
     * Perform a GET request.
     *
     * @param string $url     The URL to send the GET request to.
     * @param array  $options Additional options to modify the behavior of the GET request. Defaults to an empty array.
     *
     * @return HttpResponse The HTTP response received from the GET request.
     */
    public function get(string $url, array $options = []): HttpResponse
    {
        return $this->request('GET', $url, [...$this->default_options, ...$options]);
    }

    /**
     * Perform a DELETE request.
     *
     * @param string $url     The URL to send the DELETE request to.
     * @param array  $options Additional options to modify the behavior of the DELETE request. Defaults to an empty
     *                        array.
     *
     * @return HttpResponse The HTTP response received from the DELETE request.
     */
    public function delete(string $url, array $options = []): HttpResponse
    {
        return $this->request('DELETE', $url, [...$this->default_options, ...$options]);
    }

    /**
     * Perform a HEAD request.
     *
     * @param string $url     The URL to send the HEAD request to.
     * @param array  $options Additional options to modify the behavior of the HEAD request. Defaults to an empty array.
     *
     * @return HttpResponse The HTTP response received from the HEAD request.
     */
    public function head(string $url, array $options = []): HttpResponse
    {
        return $this->request('HEAD', $url, [...$this->default_options, ...$options]);
    }

    /**
     * Perform an OPTIONS request.
     *
     * @param string $url     The URL to send the OPTIONS request to.
     * @param array  $options Additional options to modify the behavior of the OPTIONS request. Defaults to an empty
     *                        array.
     *
     * @return HttpResponse The HTTP response received from the OPTIONS request.
     */
    public function options(string $url, array $options = []): HttpResponse
    {
        return $this->request('OPTIONS', $url, [...$this->default_options, ...$options]);
    }

    /**
     * Perform a PATCH request.
     *
     * @param string $url     The URL to send the PATCH request to.
     * @param array  $options Additional options to modify the behavior of the PATCH request. Defaults to an empty
     *                        array.
     *
     * @return HttpResponse The HTTP response received from the PATCH request.
     */
    public function patch(string $url, array $options = []): HttpResponse
    {
        return $this->request('PATCH', $url, [...$this->default_options, ...$options]);
    }

    /**
     * Perform a POST request.
     *
     * @param string $url     The URL to send the POST request to.
     * @param array  $options Additional options to modify the behavior of the POST request. Defaults to an empty array.
     *
     * @return HttpResponse The HTTP response received from the POST request.
     */
    public function post(string $url, array $options = []): HttpResponse
    {
        return $this->request('POST', $url, [...$this->default_options, ...$options]);
    }

    /**
     * Perform a PUT request.
     *
     * @param string $url     The URL to send the PUT request to.
     * @param array  $options Additional options to modify the behavior of the PUT request. Defaults to an empty array.
     *
     * @return HttpResponse The HTTP response received from the PUT request.
     */
    public function put(string $url, array $options = []): HttpResponse
    {
        return $this->request('PUT', $url, [...$this->default_options, ...$options]);
    }

    /**
     * Create a new instance of the Client class.
     *
     * @param array $config Additional configuration options for the Client. Defaults to an empty array.
     *
     * @return Client Returns a new instance of the Client class with the specified configuration options.
     */
    public function getNewClient(array $config = []): Client
    {
        return new Client($config);
    }

}