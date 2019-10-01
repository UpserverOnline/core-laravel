<?php

namespace UpserverOnline\Core\Tests\Monitors;

use Exception;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Carbon;
use UpserverOnline\Core\ApiResponse;
use UpserverOnline\Core\Tests\TestCase;
use UpserverOnline\Core\Upserver;

class FakeJob extends Job
{
    public function getJobId()
    {
        return 1337;
    }

    public function getQueue()
    {
        return 'default';
    }

    public function getRawBody()
    {
        return '{"job":"Illuminate\\\Queue\\\CallQueuedHandler@call","data":{"commandName":"App\\\Jobs\\\RefreshCache"}}';
    }
}

class CaptureFailedJobTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('upserver', [
            'app_id'      => 1,
            'app_token'   => 'secret',
            'endpoint'    => 'dummy.upserver.online',
            'failed_jobs' => true,
        ]);
    }

    /** @test */
    public function it_sends_the_failed_job_to_the_upserver_api()
    {
        Carbon::setTestNow(Carbon::parse('2019-08-01 12:00:00'));

        $job = new FakeJob;

        $exception = new Exception('Job not completed!');

        Upserver::shouldReceive('failedJob')->with(
            'database', 'default', 'UpserverOnline\Core\Tests\Monitors\FakeJob', $exception, now()->toIso8601String()
        )->andReturn(ApiResponse::fromArray([
            'data' => [
                'count'           => 0,
                'vulnerabilities' => [],
            ],
        ]));

        event(new JobFailed('database', $job, $exception));
    }
}
