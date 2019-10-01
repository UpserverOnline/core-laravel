<?php

namespace UpserverOnline\Core\Tests;

use UpserverOnline\Core\Tests\TestCase;
use UpserverOnline\Core\Upserver;

class ApiTest extends TestCase
{
    use MocksApiRequests;

    /** @test */
    public function it_can_do_a_get_request()
    {
        $this->mockApiRequest('GET', null, null, $response = ['data' => ['app_id' => 1]], 200);

        $this->assertEquals($response, Upserver::application()->json());
    }

    /** @test */
    public function it_can_do_a_post_request()
    {
        $this->mockApiRequest('POST', 'check/token/composer', ['lock_contents' => ['hash' => '123']], $response = ['data' => ['app_id' => 1]], 200);

        $this->assertEquals($response, Upserver::composer('token', ['hash' => '123'])->json());
    }
}
