<?php

namespace UpserverOnline\Core\Tests;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use UpserverOnline\Core\Middleware;
use UpserverOnline\Core\Tests\TestCase;

class MiddlewareTest extends TestCase
{
    /** @test */
    public function it_throws_a_401_error_when_the_authorization_header_is_wrong()
    {
        $request = $this->mock(Request::class)
            ->shouldReceive('header')
            ->with('Authorization', '')
            ->andReturn('Bearer wrong_token')
            ->getMock();

        try {
            (new Middleware)->handle($request, function () {
                $this->fail("Middleware should have called the 'next' closure.");
            });
        } catch (HttpException $exception) {
            return $this->assertEquals(401, $exception->getStatusCode());
        }

        $this->fail("Middleware should have thrown a 401 error.");
    }

    /** @test */
    public function it_continues_the_pipeline_if_the_autorization_header_is_right()
    {
        $request = $this->mock(Request::class)
            ->shouldReceive('header')
            ->with('Authorization', '')
            ->andReturn('Bearer ' . hash('sha256', 'secret'))
            ->getMock();

        (new Middleware)->handle($request, function () {
            $this->assertTrue(true);
        });
    }
}
