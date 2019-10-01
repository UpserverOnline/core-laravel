<?php

namespace UpserverOnline\Core\Tests\Monitors;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use UpserverOnline\Core\ApiResponse;
use UpserverOnline\Core\Controllers\Check;
use UpserverOnline\Core\Tests\TestCase;
use UpserverOnline\Core\Upserver;

class CheckTest extends TestCase
{
    /** @test */
    public function it_returns_no_errors_when_it_passes()
    {
        $symfonyRequest = SymfonyRequest::create(
            '_upserver/check', 'POST', [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT'  => 'application/json',
            ], json_encode(['token' => 1])
        );

        Upserver::shouldReceive('check')->with(1)->andReturn(ApiResponse::fromArray([
            'data' => [
                'token'   => 1,
                'monitor' => 'cache',
                'options' => ['store_name' => 'array'],
            ],
        ]));

        $controller = new Check;

        $response = $controller(Request::createFromBase($symfonyRequest));

        $data = $response->getData(true);

        $this->assertEquals([
            'data' => [
                'token'        => 1,
                'passed'       => true,
                'failed'       => false,
                'has_errors'   => false,
                'errors'       => [],
                'has_warnings' => false,
                'warnings'     => [],
            ],
        ], $data);
    }

    /** @test */
    public function it_returns_errors_when_it_fails()
    {
        $symfonyRequest = SymfonyRequest::create(
            '_upserver/check', 'POST', [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT'  => 'application/json',
            ], json_encode(['token' => 1])
        );

        Upserver::shouldReceive('check')->with(1)->andReturn(ApiResponse::fromArray([
            'data' => [
                'token'   => 1,
                'monitor' => 'cache',
                'options' => ['store_name' => 'null'],
            ],
        ]));

        $controller = new Check;

        $response = $controller(Request::createFromBase($symfonyRequest));

        $data = $response->getData(true);

        $this->assertEquals([
            'data' => [
                'token'        => 1,
                'passed'       => false,
                'failed'       => true,
                'has_errors'   => true,
                'errors'       => [
                    'config_missing' => [
                        ['data' => ['message' => 'The configuration for null is missing']],
                    ],
                ],
                'has_warnings' => false,
                'warnings'     => [],
            ],
        ], $data);
    }
}
