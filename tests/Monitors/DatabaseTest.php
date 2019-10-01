<?php

namespace UpserverOnline\Core\Tests\Monitors;

use UpserverOnline\Core\Monitors\Database;
use UpserverOnline\Core\Tests\TestCase;

class DatabaseTest extends TestCase
{
    private $sqliteFile;

    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents($this->sqliteFile, null);
    }

    protected function tearDown(): void
    {
        unlink($this->sqliteFile);

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app)
    {
        $this->sqliteFile = __DIR__ . '/known.sqlite';

        $app['config']->set('database.connections.wrong_config', [
            'driver'   => 'cqlite',
            'database' => '',
            'prefix'   => null,
        ]);

        $app['config']->set('database.connections.unknown_sqlite', [
            'driver'   => 'sqlite',
            'database' => __DIR__ . '/unknown.sqlite',
            'prefix'   => '',
        ]);

        $app['config']->set('database.connections.known_sqlite', [
            'driver'   => 'sqlite',
            'database' => $this->sqliteFile,
            'prefix'   => '',
        ]);

        $app['config']->set('database.connections.offline_mysql', [
            'driver'   => 'mysql',
            'host'     => '127.0.0.1',
            'port'     => '1337',
            'database' => 'homestead',
        ]);
    }

    /** @test */
    public function it_fails_when_the_config_is_invalid()
    {
        $monitor = new Database('wrong_config');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Database::ERROR_CONFIG_INVALID));
    }

    /** @test */
    public function it_fails_when_the_connection_is_not_configured()
    {
        $monitor = new Database('unknown_connection');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Database::ERROR_CONFIG_MISSING));
    }

    /** @test */
    public function it_passes_when_the_database_is_available()
    {
        $monitor = new Database('known_sqlite');

        $this->assertTrue($monitor->passes());
        $this->assertFalse($monitor->hasError());
    }

    /** @test */
    public function it_fails_when_the_database_is_offline()
    {
        $monitor = new Database('offline_mysql');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Database::ERROR_PDO_EXCEPTION));
    }

    /** @test */
    public function it_fails_when_the_database_file_doesnt_exists()
    {
        $monitor = new Database('unknown_sqlite');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Database::ERROR_CONNECTION));
    }
}
