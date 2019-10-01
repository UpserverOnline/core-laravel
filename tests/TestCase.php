<?php

namespace UpserverOnline\Core\Tests;

use Closure;
use Mockery;
use UpserverOnline\Core\UpserverServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function mock($abstract, Closure $mock = null)
    {
        return $this->instance($abstract, Mockery::mock(...array_filter(func_get_args())));
    }

    protected function getPackageProviders($app)
    {
        return [UpserverServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('upserver', [
            'app_id'    => 1,
            'app_token' => 'secret',
            'endpoint'  => 'dummy.upserver.online',
        ]);

        return parent::getEnvironmentSetUp($app);
    }
}
