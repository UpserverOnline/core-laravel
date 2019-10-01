<?php

namespace UpserverOnline\Core\Tests\Monitors;

use Illuminate\Redis\RedisManager;
use Illuminate\Support\Str;
use InvalidArgumentException;
use UpserverOnline\Core\Monitors\Redis;
use UpserverOnline\Core\Tests\TestCase;

class FakeRedisConnection
{
    private $name;
    private $test;

    public function __construct(string $name, $test)
    {
        $this->name = $name;

        if (Str::startsWith($this->name, 'wrong_config')) {
            throw new InvalidArgumentException;
        }

        $this->test = $test;
    }

    public function connect()
    {
        return $this;
    }

    public function isConnected()
    {
        return $this->test->redisIsConnected;
    }
}

class RedisTest extends TestCase
{
    public $redisIsConnected = false;

    protected function setUp(): void
    {
        parent::setUp();

        $test = $this;

        $realFactory = app('redis');

        $redisFactory = app()->singleton('redis', function () use ($test, $realFactory) {
            return $this->mock(RedisManager::class)
                ->shouldReceive('connection')
                ->andReturnUsing(function ($connectionName) use ($test) {
                    return new FakeRedisConnection($connectionName, $test);
                })
                ->getMock();
        });
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.redis.client', 'fake');

        $app['config']->set('database.redis.wrong_config', [
            'database' => 'wrong_config',
        ]);

        $app['config']->set('database.redis.not_connected', [
            'database' => 'not_connected',
        ]);

        $app['config']->set('database.redis.connected', [
            'database' => 'connected',
        ]);
    }

    /** @test */
    public function it_fails_when_the_connection_is_not_configured()
    {
        $monitor = new Redis('unknown_connection');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Redis::ERROR_CONFIG_MISSING));
    }

    /** @test */
    public function it_fails_when_the_config_is_invalid()
    {
        $monitor = new Redis('wrong_config');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Redis::ERROR_CONFIG_INVALID));
    }

    /** @test */
    public function it_fails_when_the_connection_is_not_connected()
    {
        $this->redisIsConnected = false;

        $monitor = new Redis('not_connected');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Redis::ERROR_CONNECTION));
    }

    /** @test */
    public function it_passes_when_the_connection_is_connected()
    {
        $this->redisIsConnected = true;

        $monitor = new Redis('connected');

        $this->assertTrue($monitor->passes());
    }
}
