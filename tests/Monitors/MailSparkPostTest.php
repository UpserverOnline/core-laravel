<?php

namespace UpserverOnline\Core\Tests\Monitors;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use UpserverOnline\Core\Monitors\Mail;
use UpserverOnline\Core\Support;
use UpserverOnline\Core\Tests\MocksGuzzleRequests;
use UpserverOnline\Core\Tests\TestCase;

class MailSparkPostTest extends TestCase
{
    use MocksGuzzleRequests;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Support::supportsSparkPostDriver()) {
            $this->markTestSkipped('SparkPost driver is not supported in this version of Laravel.');
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('services.sparkpost', [
            'secret' => 'secret',
        ]);
    }

    /** @test */
    public function it_fails_when_the_key_is_invalid()
    {
        $monitor = new Mail('sparkpost', function ($config) {
            $response = $this->mockResponse(["errors" => [
                ["message" => "Forbidden."],
                ["message" => "Really Forbidden."],
            ]]);

            $exception = $this->mock(ClientException::class)
                ->shouldReceive('getResponse')
                ->andReturn($response)
                ->getMock();

            return $this->mockRequest(
                'GET', 'https://api.sparkpost.com/api/v1/transmissions', ['headers' => ['Authorization' => 'secret']], []
            )->andThrow($exception)->getMock();
        });

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_SPARKPOST_KEY));
    }

    /** @test */
    public function it_fails_when_it_can_not_reach_sparkpost()
    {
        $monitor = new Mail('sparkpost', function ($config) {
            $exception = $this->mock(ConnectException::class);

            return $this->mockRequest(
                'GET', 'https://api.sparkpost.com/api/v1/transmissions', ['headers' => ['Authorization' => 'secret']], []
            )->andThrow($exception)->getMock();
        });

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_SPARKPOST_CONNECTION));
    }

    /** @test */
    public function it_passes_when_results_came_back()
    {
        $monitor = new Mail('sparkpost', function ($config) {
            $response = $this->mockResponse(['results' => []]);

            return $this->mockRequest(
                'GET', 'https://api.sparkpost.com/api/v1/transmissions', ['headers' => ['Authorization' => 'secret']], []
            )->andReturn($response)->getMock();
        });

        $this->assertTrue($monitor->passes());
    }

    /** @test */
    public function it_fails_when_the_no_results_are_returned()
    {
        $monitor = new Mail('sparkpost', function ($config) {
            $response = $this->mockResponse([]);

            return $this->mockRequest(
                'GET', 'https://api.sparkpost.com/api/v1/transmissions', ['headers' => ['Authorization' => 'secret']], []
            )->andReturn($response)->getMock();
        });

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_SPARKPOST_INVALID));
    }
}
