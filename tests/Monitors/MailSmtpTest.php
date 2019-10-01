<?php

namespace UpserverOnline\Core\Tests\Monitors;

use Swift_SmtpTransport;
use Swift_Transport;
use UpserverOnline\Core\Monitors\Mail;
use UpserverOnline\Core\Tests\TestCase;

class MailSmtpTest extends TestCase
{
    /** @test */
    public function it_fails_when_it_cant_instantiate_the_transport_driver()
    {
        $monitor = new Mail('fax');

        $this->assertFalse($monitor->passes());
    }

    /** @test */
    public function it_warns_when_it_cant_check_the_driver()
    {
        app('swift.transport')->extend('custom', function () {
            return $this->mock(Swift_Transport::class);
        });

        $monitor = new Mail('custom');

        $this->assertTrue($monitor->passes());
        $this->assertTrue($monitor->hasWarning(Mail::WARNING_DRIVER_UNSUPPORTED));
    }

    /** @test */
    public function it_fails_when_it_cant_connect_to_smtp()
    {
        $monitor = new Mail('smtp');

        $this->assertFalse($monitor->passes());
    }

    /** @test */
    public function it_passes_when_it_can_say_hello_to_smtp()
    {
        $monitor = new Mail('smtp');

        app('swift.transport')->extend('smtp', function () {
            return $this->mock(Swift_SmtpTransport::class)
                ->shouldReceive('executeCommand')
                ->getMock();
        });

        $this->assertTrue($monitor->passes());
    }
}
