<?php

namespace UpserverOnline\Core\Tests\Monitors;

use UpserverOnline\Core\Api;
use UpserverOnline\Core\Controllers\Application;
use UpserverOnline\Core\Tests\TestCase;

class ApplicationTest extends TestCase
{
    /** @test */
    public function it_returns_the_app_id()
    {
        $controller = new Application;

        $response = $controller();
        $data     = $response->getData(true);

        $this->assertEquals([
            'app_id' => config('upserver.app_id'),

            'upserver_package_version' => Api::PACKAGE_VERSION,
        ], $data['data']);
    }
}
