<?php

namespace UpserverOnline\Core\Tests\Monitors;

use Illuminate\Support\Facades\Artisan;
use UpserverOnline\Core\Monitors\Config;
use UpserverOnline\Core\Monitors\Database;
use UpserverOnline\Core\Support;
use UpserverOnline\Core\Tests\TestCase;

class ConfigTest extends TestCase
{
    private $storagePath;
    private $bootstrapCachePath;

    protected function setUp(): void
    {
        parent::setUp();

        chmod($this->storagePath = storage_path(), 755);
        chmod($this->bootstrapCachePath = base_path('bootstrap/cache'), 755);
    }

    protected function tearDown(): void
    {
        Artisan::call('config:clear');
        Artisan::call('route:clear');

        @unlink(app()->getCachedConfigPath());
        @unlink(app()->getCachedRoutesPath());

        if (Support::supportsEventCaching()) {
            @unlink(app()->getCachedEventsPath());
        }

        parent::tearDown();
    }

    /** @test */
    public function it_fails_when_the_app_is_set_to_debug()
    {
        config(['app.debug' => true]);

        $monitor = new Config;

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Config::ERROR_APP_DEBUG));
    }

    /** @test */
    public function it_fails_when_the_storage_path_is_not_writable()
    {
        $monitor = new Config;

        $this->assertTrue($monitor->passes());

        chmod($this->storagePath, 600);

        $this->assertFalse($monitor->passes());
    }

    /** @test */
    public function it_fails_when_the_bootstrap_cache_path_is_not_writable()
    {
        $monitor = new Config;

        $this->assertTrue($monitor->passes());

        chmod($this->bootstrapCachePath, 600);

        $this->assertFalse($monitor->passes());
    }

    /** @test */
    public function it_warns_when_the_config_is_not_cached()
    {
        $monitor = new Config;

        $this->assertTrue($monitor->passes());
        $this->assertTrue($monitor->hasWarning(Config::WARNING_CONFIG_NOT_CACHED));

        file_put_contents(app()->getCachedConfigPath(), '');

        $this->assertTrue($monitor->passes());
        $this->assertFalse($monitor->hasWarning(Config::WARNING_CONFIG_NOT_CACHED));
    }

    /** @test */
    public function it_warns_when_the_routes_are_not_cached()
    {
        $monitor = new Config;

        $this->assertTrue($monitor->passes());
        $this->assertTrue($monitor->hasWarning(Config::WARNING_ROUTES_NOT_CACHED));

        file_put_contents(app()->getCachedRoutesPath(), '');

        $this->assertTrue($monitor->passes());
        $this->assertFalse($monitor->hasWarning(Config::WARNING_ROUTES_NOT_CACHED));
    }

    /** @test */
    public function it_warns_when_the_events_are_not_cached()
    {
        $monitor = new Config;

        $this->assertTrue($monitor->passes());

        if (Support::supportsEventCaching()) {
            $this->assertTrue($monitor->hasWarning(Config::WARNING_EVENTS_NOT_CACHED));
            file_put_contents(app()->getCachedEventsPath(), '');

            $this->assertTrue($monitor->passes());
            $this->assertFalse($monitor->hasWarning(Config::WARNING_EVENTS_NOT_CACHED));
        }
    }
}
