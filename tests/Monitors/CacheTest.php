<?php

namespace UpserverOnline\Core\Tests\Monitors;

use UpserverOnline\Core\Monitors\Cache;
use UpserverOnline\Core\Tests\TestCase;

class CacheTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.stores.wrong_config', [
            'driver' => 'nope',
        ]);

        $app['config']->set('cache.stores.null', [
            'driver' => 'null',
        ]);
    }

    /** @test */
    public function it_fails_when_the_config_is_invalid()
    {
        $monitor = new Cache('wrong_config');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Cache::ERROR_CONFIG_INVALID));
    }

    /** @test */
    public function it_fails_when_the_connection_is_not_configured()
    {
        $monitor = new Cache('unknown_store');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Cache::ERROR_CONFIG_MISSING));
    }

    /** @test */
    public function it_passes_when_it_remembers_a_value()
    {
        $monitor = new Cache('array');

        $this->assertTrue($monitor->passes());
    }

    /** @test */
    public function it_fails_when_it_doesnt_remember_the_value()
    {
        $monitor = new Cache('null');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Cache::ERROR_CACHE_MISS));
    }

    /** @test */
    public function it_fails_when_it_cant_connect_to_the_store()
    {
        $monitor = new Cache('database');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Cache::ERROR_CONNECTION));
    }
}
