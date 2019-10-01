<?php

namespace UpserverOnline\Core\Tests\Monitors;

use UpserverOnline\Core\Controllers\Configuration;
use UpserverOnline\Core\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    /** @test */
    public function it_returns_the_configuration()
    {
        $controller = new Configuration;

        $response = $controller();
        $data     = $response->getData(true);

        $this->assertTrue(array_key_exists('data', $data));
        $this->assertTrue(array_key_exists('database', $data['data']));
    }
}
