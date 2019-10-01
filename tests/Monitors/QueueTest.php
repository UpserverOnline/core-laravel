<?php

namespace UpserverOnline\Core\Tests\Monitors;

use Illuminate\Support\Str;
use UpserverOnline\Core\Monitors\Queue;
use UpserverOnline\Core\Process;
use UpserverOnline\Core\Tests\TestCase;

class QueueTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('queue.default', 'redis');
    }

    private function mockProcess($processRow, $path = null, $windows = false)
    {
        $path = $path ?: base_path();

        $process = $this->mock(Process::class);

        $process->shouldReceive('isWindowsEnvironment')->andReturn($windows);

        $process->shouldReceive('run')->withArgs(function ($command) {
            return Str::startsWith($command, 'exec ps axo pid,command');
        })->andReturn(collect([$processRow]));

        $process->shouldReceive('run')->withArgs(function ($command) {
            return Str::startsWith($command, 'lsof -p 1337');
        })->andReturn(collect([$path]));
    }

    /** @test */
    public function it_fails_when_the_connection_is_not_configured()
    {
        $monitor = new Queue('unknown_connection');

        $this->assertFalse($monitor->passes());
        $this->assertTrue($monitor->hasError(Queue::ERROR_CONFIG_MISSING));
    }

    /** @test */
    public function it_passes_when_the_queue_worker_is_running_the_default_queue()
    {
        $this->mockProcess('1337 php artisan queue:work');

        $monitor = new Queue('redis');

        $processes = $monitor->getQueueProcesses();

        $this->assertCount(1, $processes);

        $this->assertEquals([
            'pid'              => '1337',
            'command'          => 'php artisan queue:work',
            'is_worker'        => true,
            'is_worker_daemon' => false,
            'is_listener'      => false,
        ], $processes->first());

        $this->assertTrue($monitor->passes());
    }

    /** @test */
    public function it_passes_when_the_queue_listener_is_running_the_default_queue()
    {
        $this->mockProcess('1337 php artisan queue:listen');

        $monitor = new Queue('redis');

        $processes = $monitor->getQueueProcesses();

        $this->assertCount(1, $processes);

        $this->assertEquals([
            'pid'              => '1337',
            'command'          => 'php artisan queue:listen',
            'is_worker'        => false,
            'is_worker_daemon' => false,
            'is_listener'      => true,
        ], $processes->first());

        $this->assertTrue($monitor->passes());
    }

    /** @test */
    public function it_passes_when_the_queue_worker_is_running_the_default_queue_as_daemon()
    {
        $this->mockProcess('1337 php artisan queue:work --daemon');

        $monitor = new Queue('redis');

        $processes = $monitor->getQueueProcesses();

        $this->assertCount(1, $processes);

        $this->assertEquals([
            'pid'              => '1337',
            'command'          => 'php artisan queue:work --daemon',
            'is_worker'        => false,
            'is_worker_daemon' => true,
            'is_listener'      => false,
        ], $processes->first());

        $this->assertTrue($monitor->passes());
    }

    /** @test */
    public function it_passes_when_the_queue_worker_is_running_a_specific_queue()
    {
        $this->mockProcess('1337 php artisan queue:work --queue=database');

        $monitor = new Queue('database');

        $processes = $monitor->getQueueProcesses();

        $this->assertCount(1, $processes);

        $this->assertEquals([
            'pid'              => '1337',
            'command'          => 'php artisan queue:work --queue=database',
            'is_worker'        => true,
            'is_worker_daemon' => false,
            'is_listener'      => false,
        ], $processes->first());

        $this->assertTrue($monitor->passes());
    }

    /** @test */
    public function it_fails_when_the_queue_worker_is_for_the_default_but_the_monitor_for_another()
    {
        $this->mockProcess('1337 php artisan queue:work');

        $monitor = new Queue('database');

        $processes = $monitor->getQueueProcesses();

        $this->assertCount(0, $processes);
        $this->assertFalse($monitor->passes());
    }

    /** @test */
    public function it_fails_when_the_queue_worker_is_for_another_but_the_monitor_for_the_default()
    {
        $this->mockProcess('1337 php artisan queue:work --queue=database');

        $monitor = new Queue('redis');

        $processes = $monitor->getQueueProcesses();

        $this->assertCount(0, $processes);
        $this->assertFalse($monitor->passes());
    }

    /** @test */
    public function it_fails_when_the_queue_worker_is_for_another_application()
    {
        $this->mockProcess('1337 php artisan queue:work', '/home/forge/dummy.com');

        $monitor = new Queue('redis');

        $processes = $monitor->getQueueProcesses();

        $this->assertCount(0, $processes);
        $this->assertFalse($monitor->passes());
    }

    /** @test */
    public function it_passes_but_still_warns_whenever_the_monitors_runs_on_a_windows_server()
    {
        $this->mockProcess('1337 php artisan queue:work', null, true);

        $monitor = new Queue('redis');

        $processes = $monitor->getQueueProcesses();

        $this->assertCount(0, $processes);
        $this->assertTrue($monitor->passes());
        $this->assertTrue($monitor->hasWarning(Queue::WARNING_WINDOWS_OS_UNSUPPORTED));
    }
}
