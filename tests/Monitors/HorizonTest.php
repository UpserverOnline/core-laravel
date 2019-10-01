<?php

namespace UpserverOnline\Core\Tests\Monitors;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use UpserverOnline\Core\Monitors\Horizon;
use UpserverOnline\Core\Tests\TestCase;

class HorizonTest extends TestCase
{
    private function mockSupervisor(bool $hasSupervisors)
    {
        return $this->mock(MasterSupervisorRepository::class)
            ->shouldReceive('all')
            ->andReturn($hasSupervisors ? [
                (object) [],
            ] : [])
            ->getMock();
    }

    /** @test */
    public function it_fails_when_horizon_is_not_installed()
    {
        $monitor = new Horizon;

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Horizon::ERROR_HORIZON_NOT_INSTALLED));
    }

    /** @test */
    public function it_passes_when_horizon_is_running()
    {
        $this->mockSupervisor(true);

        $monitor = new Horizon;

        $this->assertTrue($monitor->passes());
    }

    /** @test */
    public function it_fails_when_horizon_is_not_running()
    {
        $this->mockSupervisor(false);

        $monitor = new Horizon;

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Horizon::ERROR_HORIZON_NOT_RUNNING));
    }
}
