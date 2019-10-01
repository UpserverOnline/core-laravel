<?php

namespace UpserverOnline\Core\Tests\Monitors;

use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Pusher\PusherException;
use UpserverOnline\Core\Monitors\Pusher;
use UpserverOnline\Core\Tests\TestCase;

class PusherTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('broadcasting.connections.wrong_config', [
            'driver' => 'pusher',
        ]);

        $app['config']->set('broadcasting.connections.redis', [
            'driver' => 'redis',
        ]);

        $app['config']->set('broadcasting.connections.pusher', [
            'driver' => 'pusher',
            'key'    => 'key',
            'secret' => 'secret',
            'app_id' => '1',
        ]);
    }

    /** @test */
    public function it_fails_when_the_config_is_invalid()
    {
        $monitor = new Pusher('wrong_config');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Pusher::ERROR_CONFIG_INVALID));
    }

    /** @test */
    public function it_fails_when_the_driver_is_not_redis()
    {
        $monitor = new Pusher('redis');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Pusher::ERROR_NON_PUSHER_DRIVER));
    }

    /** @test */
    public function it_fails_when_the_connection_is_not_configured()
    {
        $monitor = new Pusher('unknown_connection');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Pusher::ERROR_CONFIG_MISSING));
    }

    private function fakeCreator($channels)
    {
        app(BroadcastingFactory::class)->extend('pusher', function ($app, $config) use ($channels) {
            return new FakePusherBroadcaster(new FakePusher($channels));
        });
    }

    /** @test */
    public function it_passes_when_it_can_connect_to_pusher()
    {
        $monitor = new Pusher('pusher');

        $this->fakeCreator([]);

        $this->assertTrue($monitor->passes());
    }

    /** @test */
    public function it_fails_when_it_can_not_connect_to_pusher()
    {
        $monitor = new Pusher('pusher');

        $this->fakeCreator(new PusherException);

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Pusher::ERROR_CONNECTION));
    }

    /** @test */
    public function it_fails_when_it_can_not_fetch_channels()
    {
        $monitor = new Pusher('pusher');

        $this->fakeCreator(false);

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Pusher::ERROR_FETCH_CHANNELS));
    }
}

class FakePusherBroadcaster
{
    private $pusher;

    public function __construct($pusher)
    {
        $this->pusher = $pusher;
    }

    public function getPusher()
    {
        return $this->pusher;
    }
}

class FakePusher
{
    private $channels;

    public function __construct($channels)
    {
        $this->channels = $channels;
    }

    public function get_channels()
    {
        if ($this->channels instanceof \Exception) {
            throw $this->channels;
        }

        return $this->channels;
    }
}
