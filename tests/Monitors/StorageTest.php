<?php

namespace UpserverOnline\Core\Tests\Monitors;

use UpserverOnline\Core\Monitors\Storage;
use UpserverOnline\Core\Tests\TestCase;

class StorageTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('filesystems.disks.wrong_config', [
            'driver' => 'floppy',
        ]);

        $app['config']->set('filesystems.disks.ftp', [
            'driver' => 'ftp',
        ]);
    }

    /** @test */
    public function it_fails_when_the_config_is_invalid()
    {
        $monitor = new Storage('wrong_config');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Storage::ERROR_CONFIG_INVALID));
    }

    /** @test */
    public function it_fails_when_the_connection_is_not_configured()
    {
        $monitor = new Storage('unknown_store');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Storage::ERROR_CONFIG_MISSING));
    }

    /** @test */
    public function it_fails_when_it_cant_reach_the_disk()
    {
        $monitor = new Storage('ftp');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Storage::ERROR_CONNECTION));
    }

    /** @test */
    public function it_passes_when_it_can_fetch_the_root_directory()
    {
        $monitor = new Storage('local');

        $this->assertTrue($monitor->passes());
    }
}
