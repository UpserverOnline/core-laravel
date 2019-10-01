<?php

namespace UpserverOnline\Core\Tests;

use GuzzleHttp\Client as HttpClient;
use UpserverOnline\Core\Api;
use UpserverOnline\Core\Tests\MocksGuzzleRequests;

trait MocksApiRequests
{
    use MocksGuzzleRequests;

    protected $httpClient;

    protected function mockHttpClient()
    {
        $this->httpClient = $this->mock(HttpClient::class);

        app()->singleton('upserver-online.api-client', function () {
            return $this->httpClient;
        });
    }

    private function mockApiRequest($method, $path = null, $payload = null, $jsonableContent = null, $statusCode = null)
    {
        if (!$this->httpClient) {
            $this->mockHttpClient();
        };

        $response = $this->mockResponse($jsonableContent);

        if ($statusCode) {
            $response->shouldReceive('getStatusCode')->andReturn($statusCode);
        }

        $this->httpClient
            ->shouldReceive('request')
            ->with($method, "https://dummy.upserver.online/api/application/1/{$path}", [
                'headers' => [
                    'Authorization'            => 'Bearer secret',
                    'Accept'                   => 'application/json',
                    'Content-Type'             => 'application/json',
                    'Upserver-Package-Version' => Api::PACKAGE_VERSION,
                ],
            ] + ($payload ? ['json' => $payload] : []))
            ->andReturn($response);
    }
}
