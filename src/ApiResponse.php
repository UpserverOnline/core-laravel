<?php

namespace UpserverOnline\Core;

use Psr\Http\Message\ResponseInterface;

class ApiResponse
{
    /**
     * Response body contents.
     *
     * @var string
     */
    private $body;

    /**
     * Response status code.
     *
     * @var int
     */
    private $statusCode;

    /**
     * The original PSR response from where this instance was build.
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $response;

    /**
     * @param string                 $body
     * @param int                    $statusCode
     * @param \Psr\Http\Message\ResponseInterface|null $response
     */
    public function __construct(string $body, int $statusCode, ResponseInterface $response = null)
    {
        $this->body       = $body;
        $this->statusCode = $statusCode;
        $this->response   = $response;
    }

    /**
     * Creates a new instance from the given contents and status code.
     *
     * @param  array  $contents
     * @param  int    $statusCode
     * @return $this
     */
    public static function fromArray(array $contents, int $statusCode = 200): ApiResponse
    {
        return new static(json_encode($contents), $statusCode);
    }

    /**
     * Create a new instance from the given PSR Response
     *
     * @param  \Psr\Http\Message\ResponseInterface $response
     * @return $this
     */
    public static function fromPsrResponse(ResponseInterface $response): ApiResponse
    {
        return new static($response->getBody()->getContents(), $response->getStatusCode(), $response);
    }

    /**
     * Decodes the JSON data from the body
     *
     * @return mixed
     */
    public function json()
    {
        return json_decode($this->body, true);
    }

    /**
     * Get an item from the JSON data using "dot" notation.
     *
     * @param  string|array|int  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->isSuccessful() ? data_get($this->json(), $key, $default) : value($default);
    }

    /**
     * Returns the status code.
     *
     * @return int
     */
    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returns a boolean wether the request was successful.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
