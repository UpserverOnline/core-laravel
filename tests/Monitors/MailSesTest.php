<?php

namespace UpserverOnline\Core\Tests\Monitors;

use Aws\Result;
use Aws\Ses\Exception\SesException;
use Aws\Ses\SesClient;
use Illuminate\Mail\Transport\SesTransport;
use UpserverOnline\Core\Monitors\Mail;
use UpserverOnline\Core\Tests\TestCase;

class MailSesTest extends TestCase
{
    private function mockTransport($sesClient)
    {
        $this->mailTransportManager()->extend('ses', function () use ($sesClient) {
            return $this->mock(SesTransport::class)
                ->shouldReceive('ses')
                ->andReturn($sesClient)
                ->getMock();
        });
    }

    /** @test */
    public function it_fails_when_the_key_is_invalid()
    {
        $exception = $this->mock(SesException::class)
            ->shouldReceive('getAwsErrorCode')
            ->andReturn('InvalidClientTokenId')
            ->shouldReceive('getAwsErrorMessage')
            ->andReturn('The security token included in the request is invalid.')
            ->getMock();

        $sesClient = $this->mock(SesClient::class)
            ->shouldReceive('getSendQuota')
            ->andThrow($exception)
            ->getMock();

        $this->mockTransport($sesClient);

        $monitor = new Mail('ses');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_SES_KEY));
    }

    /** @test */
    public function it_fails_when_the_the_request_throws_an_exception()
    {
        $exception = $this->mock(SesException::class)
            ->shouldReceive('getAwsErrorCode')
            ->andReturn('AccessDeniedException')
            ->shouldReceive('getAwsErrorMessage')
            ->andReturn('You do not have sufficient access to perform this action.')
            ->getMock();

        $sesClient = $this->mock(SesClient::class)
            ->shouldReceive('getSendQuota')
            ->andThrow($exception)
            ->getMock();

        $this->mockTransport($sesClient);

        $monitor = new Mail('ses');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_SES_CONNECTION));
    }

    /** @test */
    public function it_fails_when_the_response_is_empty()
    {
        $result = $this->mock(Result::class)
            ->shouldReceive('toArray')
            ->andReturn([])
            ->getMock();

        $sesClient = $this->mock(SesClient::class)
            ->shouldReceive('getSendQuota')
            ->andReturn($result)
            ->getMock();

        $this->mockTransport($sesClient);

        $monitor = new Mail('ses');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Mail::ERROR_SES_INVALID));
    }

    /** @test */
    public function it_passes_when_the_response_returns_the_quota()
    {
        $result = $this->mock(Result::class)
            ->shouldReceive('toArray')
            ->andReturn(['Max24HourSend' => 1337])
            ->getMock();

        $sesClient = $this->mock(SesClient::class)
            ->shouldReceive('getSendQuota')
            ->andReturn($result)
            ->getMock();

        $this->mockTransport($sesClient);

        $monitor = new Mail('ses');

        $this->assertTrue($monitor->passes());
    }
}
