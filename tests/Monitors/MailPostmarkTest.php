<?php

namespace UpserverOnline\Core\Tests\Monitors;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use UpserverOnline\Core\Monitors\Mail;
use UpserverOnline\Core\Support;
use UpserverOnline\Core\Tests\MocksGuzzleRequests;
use UpserverOnline\Core\Tests\TestCase;

class MailPostmarkTest extends TestCase
{
    use MocksGuzzleRequests;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Support::supportsPostmarkDriver()) {
            $this->markTestSkipped('Postmark driver is not supported in this version of Laravel.');
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('services.postmark', [
            'token' => 'secret',
        ]);
    }

    /** @test */
    public function it_fails_when_the_key_is_invalid()
    {
        $monitor = new Mail('postmark', function ($config) {
            $response = $this->mockResponse([
                "ErrorCode" => 10,
                "Message"   => "The Server Token you provided in the X-Postmark-Server-Token request header was invalid. Please verify that you are using a valid token.",
            ]);

            $exception = $this->mock(ClientException::class)
                ->shouldReceive('getResponse')
                ->andReturn($response)
                ->getMock();

            return $this->mockRequest(
                'GET', 'https://api.postmarkapp.com/messages/outbound?count=1&offset=0', ['headers' => [
                    'X-Postmark-Server-Token' => 'secret',
                    'Content-Type'            => 'application/json',
                ]]
            )->andThrow($exception)->getMock();
        });

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_POSTMARK_KEY));
    }

    /** @test */
    public function it_fails_when_it_can_not_reach_sparkpost()
    {
        $monitor = new Mail('postmark', function ($config) {
            $exception = $this->mock(ConnectException::class);

            return $this->mockRequest(
                'GET', 'https://api.postmarkapp.com/messages/outbound?count=1&offset=0', ['headers' => [
                    'X-Postmark-Server-Token' => 'secret',
                    'Content-Type'            => 'application/json',
                ]]
            )->andThrow($exception)->getMock();
        });

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_POSTMARK_CONNECTION));
    }

    /** @test */
    public function it_passes_when_results_came_back()
    {
        $monitor = new Mail('postmark', function ($config) {
            $response = $this->mockResponse(['Messages' => [], 'TotalCount' => 0]);

            return $this->mockRequest(
                'GET', 'https://api.postmarkapp.com/messages/outbound?count=1&offset=0', ['headers' => [
                    'X-Postmark-Server-Token' => 'secret',
                    'Content-Type'            => 'application/json',
                ]]
            )->andReturn($response)->getMock();
        });

        $this->assertTrue($monitor->passes());
    }

    /** @test */
    public function it_fails_when_the_no_results_are_returned()
    {
        $monitor = new Mail('postmark', function ($config) {
            $response = $this->mockResponse([]);

            return $this->mockRequest(
                'GET', 'https://api.postmarkapp.com/messages/outbound?count=1&offset=0', ['headers' => [
                    'X-Postmark-Server-Token' => 'secret',
                    'Content-Type'            => 'application/json',
                ]]
            )->andReturn($response)->getMock();
        });

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_POSTMARK_INVALID));
    }
}
