<?php

namespace UpserverOnline\Core\Tests\Monitors;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use UpserverOnline\Core\Monitors\Mail;
use UpserverOnline\Core\Tests\MocksGuzzleRequests;
use UpserverOnline\Core\Tests\TestCase;

class MailMailgunTest extends TestCase
{
    use MocksGuzzleRequests;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('services.mailgun', [
            'domain' => 'sandbox.mailgun.org',
            'secret' => 'secret',
        ]);
    }

    /** @test */
    public function it_fails_if_the_domain_is_invalid()
    {
        $monitor = new Mail('mailgun', function ($config) {
            $response = $this->mockResponse(["message" => "Domain not found"]);

            $exception = $this->mock(ClientException::class)
                ->shouldReceive('getResponse')
                ->andReturn($response)
                ->getMock();

            return $this->mockRequest(
                'GET', 'https://api.mailgun.net/v3/domains/sandbox.mailgun.org', ['auth' => ['api', 'secret']]
            )->andThrow($exception)->getMock();
        });

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_MAILGUN_DOMAIN));
    }

    /** @test */
    public function it_fails_if_the_domain_is_disabled()
    {
        $monitor = new Mail('mailgun', function ($config) {
            $response = $this->mockResponse(['domain' => ['is_disabled' => true, 'state' => 'active']]);

            return $this->mockRequest(
                'GET', 'https://api.mailgun.net/v3/domains/sandbox.mailgun.org', ['auth' => ['api', 'secret']]
            )->andReturn($response)->getMock();
        });

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_MAILGUN_DISABLED));
    }

    /** @test */
    public function it_fails_if_the_domain_is_not_active()
    {
        $monitor = new Mail('mailgun', function ($config) {
            $response = $this->mockResponse(['domain' => ['is_disabled' => false, 'state' => 'not-active']]);

            return $this->mockRequest(
                'GET', 'https://api.mailgun.net/v3/domains/sandbox.mailgun.org', ['auth' => ['api', 'secret']]
            )->andReturn($response)->getMock();
        });

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_MAILGUN_STATE));
    }

    /** @test */
    public function it_fails_when_it_can_not_reach_mailgun()
    {
        $monitor = new Mail('mailgun', function ($config) {
            $exception = $this->mock(ConnectException::class);

            return $this->mockRequest(
                'GET', 'https://api.mailgun.net/v3/domains/sandbox.mailgun.org', ['auth' => ['api', 'secret']]
            )->andThrow($exception)->getMock();
        });

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_MAILGUN_CONNECTION));
    }

    /** @test */
    public function it_passes_when_the_domain_is_enabled_and_active()
    {
        $monitor = new Mail('mailgun', function ($config) {
            $response = $this->mockResponse(['domain' => ['is_disabled' => false, 'state' => 'active']]);

            return $this->mockRequest(
                'GET', 'https://api.mailgun.net/v3/domains/sandbox.mailgun.org', ['auth' => ['api', 'secret']]
            )->andReturn($response)->getMock();
        });

        $this->assertTrue($monitor->passes());
    }
}
