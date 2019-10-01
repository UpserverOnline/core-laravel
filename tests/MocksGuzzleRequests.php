<?php

namespace UpserverOnline\Core\Tests;

use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\ResponseInterface;

trait MocksGuzzleRequests
{
    protected function mockResponse($jsonableContent)
    {
        return $this->mock(ResponseInterface::class)
            ->shouldReceive('getBody')
            ->andReturnSelf()
            ->shouldReceive('getContents')
            ->andReturn(json_encode($jsonableContent))
            ->getMock();
    }

    protected function mockRequest($method, $uri, $options, $moreOptions = null)
    {
        return $this->mock(HttpClient::class)
            ->shouldReceive('request')
            ->withArgs(array_filter(func_get_args(), function ($arg) {
                return !is_null($arg);
            }));
    }
}
