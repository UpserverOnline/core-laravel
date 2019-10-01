<?php

namespace UpserverOnline\Core;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Str;
use \Exception;

class Api
{
    /**
     * The package version.
     *
     * @var string
     */
    const PACKAGE_VERSION = '1.0.3';

    /**
     * The HTTP client that handles the Api requests.
     *
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * App ID.
     *
     * @var string
     */
    private $appId;

    /**
     * App Token.
     *
     * @var string
     */
    private $appToken;

    /**
     * The Upserver Api domain
     *
     * @var string
     */
    private $endpoint;

    /**
     * Wether to use HTTP or HTTPS to make Api calls.
     *
     * @var bool
     */
    private $secure = true;

    /**
     * @param \GuzzleHttp\Client $client
     * @param string     $appId
     * @param string     $appToken
     * @param string     $endpoint
     */
    public function __construct(HttpClient $client, string $appId, string $appToken, string $endpoint = null)
    {
        $this->client   = $client;
        $this->appId    = $appId;
        $this->appToken = $appToken;
        $this->endpoint = $endpoint ?: 'upserver.online';
    }

    /**
     * Returns the 'secure' setting
     *
     * @return bool
     */
    public function secure()
    {
        return $this->secure;
    }

    /**
     * Set the 'secure' boolean
     *
     * @return $this
     */
    public function setSecure(bool $secure = true)
    {
        $this->secure = $secure;

        return $this;
    }

    /**
     * Generates an Api url.
     *
     * @param  string $path
     * @return string
     */
    private function url($path = null): string
    {
        $protocol = $this->secure ? 'https' : 'http';

        return "{$protocol}://{$this->endpoint}/api/application/{$this->appId}/{$path}";
    }

    /**
     * Returns the headers for the Api request.
     *
     * @return array
     */
    private function headers(): array
    {
        return [
            'Authorization'            => "Bearer {$this->appToken}",
            'Accept'                   => 'application/json',
            'Content-Type'             => 'application/json',
            'Upserver-Package-Version' => static::PACKAGE_VERSION,
        ];
    }

    /**
     * Calls a GET request on the Upserver Api.
     *
     * @param  string $url
     * @return \UpserverOnline\Core\ApiResponse
     */
    private function get(string $url): ApiResponse
    {
        $response = $this->client->request(
            'GET', $url, ['headers' => $this->headers()]
        );

        return ApiResponse::fromPsrResponse($response);
    }

    /**
     * Calls a POST request on the Upserver Api.
     *
     * @param  string $url
     * @param  array $data
     * @return \UpserverOnline\Core\ApiResponse
     */
    private function post(string $url, array $data): ApiResponse
    {
        $response = $this->client->request(
            'POST', $url, ['headers' => $this->headers(), 'json' => $data]
        );

        return ApiResponse::fromPsrResponse($response);
    }

    /**
     * Fetches the application.
     *
     * @return \UpserverOnline\Core\ApiResponse
     */
    public function application(): ApiResponse
    {
        return $this->get($this->url());
    }

    /**
     * Fetches a check.
     *
     * @param  string $token
     * @return \UpserverOnline\Core\ApiResponse
     */
    public function check(string $token): ApiResponse
    {
        return $this->get($this->url("check/{$token}"));
    }

    /**
     * Checks the composer.lock content.
     *
     * @param  string $token
     * @param  array $lockContents
     * @return \UpserverOnline\Core\ApiResponse
     */
    public function composer(string $token, array $lockContents): ApiResponse
    {
        return $this->post(
            $this->url("check/{$token}/composer"),
            ['lock_contents' => $lockContents]
        );
    }

    /**
     * Captures a failed job
     *
     * @param  string $connection
     * @param  string $queue
     * @param  string $displayName
     * @param  Exception $exception
     * @param  string $failedAt
     * @return \UpserverOnline\Core\ApiResponse
     */
    public function failedJob(string $connection, string $queue, string $displayName, Exception $exception, string $failedAt): ApiResponse
    {
        return $this->post($this->url("failedJob"), [
            'connection'        => $connection,
            'queue'             => $queue,
            'display_name'      => $displayName,
            'exception'         => (string) $exception,
            'exception_class'   => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_code'    => $exception->getCode(),
            'exception_file'    => Str::after($exception->getFile(), base_path() . '/'),
            'exception_line'    => $exception->getLine(),
            'failed_at'         => $failedAt,
        ]);
    }
}
