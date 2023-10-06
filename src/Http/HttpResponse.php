<?php
/**
 * This file contains the HttpResponse class.
 */

namespace Charm\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Class HttpResponse
 *
 * A single HTTP response
 *
 * @package Charm\Http
 */
class HttpResponse
{
    protected ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getBody(): string
    {
        return (string) $this->response->getBody();
    }

    public function getJsonBody($asarray = true): mixed
    {
        return json_decode($this->getBody(), $asarray);
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    public function getGuzzleResponse(): ResponseInterface
    {
        return $this->response;
    }
}