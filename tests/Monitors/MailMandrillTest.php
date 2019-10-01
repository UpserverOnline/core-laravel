<?php

namespace UpserverOnline\Core\Tests\Monitors;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use UpserverOnline\Core\Monitors\Mail;
use UpserverOnline\Core\Support;
use UpserverOnline\Core\Tests\MocksGuzzleRequests;
use UpserverOnline\Core\Tests\TestCase;

class MailMandrillTest extends TestCase
{
    use MocksGuzzleRequests;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Support::supportsMandrillDriver()) {
            $this->markTestSkipped('Mandrill driver is not supported in this version of Laravel.');
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('services.mandrill', [
            'secret' => 'secret',
        ]);
    }

    /** @test */
    public function it_fails_if_the_key_is_invalid()
    {
        $monitor = new Mail('mandrill', function ($config) {
            $response = $this->mockResponse(["message" => "Invalid API key"]);

            $exception = $this->mock(ClientException::class)
                ->shouldReceive('getResponse')
                ->andReturn($response)
                ->getMock();

            return $this->mockRequest(
                'POST', 'https://mandrillapp.com/api/1.0/users/ping2.json', ['form_params' => ['key' => 'secret']]
            )->andThrow($exception)->getMock();
        });

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_MANDRILL_KEY));
    }

    /** @test */
    public function it_fails_if_the_connection_fails()
    {
        $monitor = new Mail('mandrill', function ($config) {
            $exception = $this->mock(ConnectException::class);

            return $this->mockRequest(
                'POST', 'https://mandrillapp.com/api/1.0/users/ping2.json', ['form_params' => ['key' => 'secret']]
            )->andThrow($exception)->getMock();
        });

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_MANDRILL_CONNECTION));
    }

    /** @test */
    public function it_fails_when_it_doesnt_ping_pong()
    {
        $monitor = new Mail('mandrill', function ($config) {
            $response = $this->mockResponse(["PONG" => "PING!"]);

            return $this->mockRequest(
                'POST', 'https://mandrillapp.com/api/1.0/users/ping2.json', ['form_params' => ['key' => 'secret']]
            )->andReturn($response)->getMock();
        });

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_MANDRILL_INVALID));
    }

    /** @test */
    public function it_passes_when_it_ping_pongs()
    {
        $monitor = new Mail('mandrill', function ($config) {
            $response = $this->mockResponse(["PING" => "PONG!"]);

            return $this->mockRequest(
                'POST', 'https://mandrillapp.com/api/1.0/users/ping2.json', ['form_params' => ['key' => 'secret']]
            )->andReturn($response)->getMock();
        });

        $this->assertTrue($monitor->passes());
    }
}
