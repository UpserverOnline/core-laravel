<?php

namespace UpserverOnline\Core\Tests;

use Illuminate\Support\Arr;
use UpserverOnline\Core\ConfigurationCollector;
use UpserverOnline\Core\Support;

class ConfigurationCollectorTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.stores', [
            'apc' => [
                'driver' => 'apc',
            ],

            'array' => [
                'driver' => 'array',
            ],

            'database' => [
                'driver'     => 'database',
                'table'      => 'cache',
                'connection' => null,
            ],
        ]);

        $app['config']->set('broadcasting.connections', [
            'only_pusher' => [
                'driver' => 'pusher',
            ],
            'not_redis' => [
                'driver' => 'redis',
            ],
        ]);

        $app['config']->set('database.redis', [
            'client' => 'predis',

            'default' => [
                'host'     => '127.0.0.1',
                'port'     => 6379,
                'database' => 0,
            ],
        ]);
    }

    /** @test */
    public function it_collects_all_relevant_connections_and_stores()
    {
        $config = ConfigurationCollector::get();

        $mail = $config['mail']['drivers'];

        $this->assertTrue(in_array('ses', $mail));
        $this->assertTrue(in_array('smtp', $mail));

        $this->assertEquals([
            "cache" => [
                "stores" => [
                    "apc",
                    "array",
                    "database",
                ],
            ],

            "database" => [
                "connections" => [
                    "testing",
                    "sqlite",
                    "mysql",
                    "pgsql",
                    "sqlsrv",
                ],
            ],

            "pusher" => [
                "connections" => [
                    "only_pusher",
                ],
            ],

            "queue" => [
                "connections" => [
                    "sync",
                    "database",
                    "beanstalkd",
                    "sqs",
                    "redis",
                ],
            ],

            "redis" => [
                "connections" => [
                    "default",
                ],
            ],

            "storage" => [
                "disks" => [
                    "local",
                    "public",
                    "s3",
                ],
            ],
        ], Arr::except($config, ['mail']));
    }

    /** @test */
    public function it_supports_the_modern_mail_manager()
    {
        if (!Support::supportsMultipleMailers()) {
            $this->markTestSkipped('Modern mail manager is not supported in this version of Laravel.');
        }

        config(['mail.mailers' => [
            'custom' => [],
            'smtp'   => [],
        ]]);

        $config = ConfigurationCollector::get();

        $mail = $config['mail']['drivers'];

        $this->assertTrue(in_array('custom', $mail));
        $this->assertTrue(in_array('smtp', $mail));
    }
}
