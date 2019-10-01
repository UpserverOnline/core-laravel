<?php

namespace UpserverOnline\Core\Tests\Monitors;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;
use Illuminate\Queue\Failed\FailedJobProviderInterface as QueueFailer;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use UpserverOnline\Core\Controllers\FailedJobs;
use UpserverOnline\Core\Tests\TestCase;

class FailedJobsTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('queue.failed', [
            'database' => 'testbench',
            'table'    => 'failed_jobs',
        ]);
    }

    /** @test */
    public function it_responds_with_422_if_the_repository_is_not_the_database_one()
    {
        $queueFailer = $this->mock(QueueFailer::class);

        $controller = new FailedJobs;

        try {
            $controller($queueFailer);
        } catch (HttpException $exception) {
            return $this->assertEquals(422, $exception->getStatusCode());
        }

        $this->fail("Controller should have responded with 422");
    }

    /** @test */
    public function it_fetches_the_failed_jobs_from_the_database()
    {
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        $controller = new FailedJobs;

        $response = $controller(
            $this->mock(DatabaseFailedJobProvider::class)
        );

        $this->assertInstanceOf(LengthAwarePaginator::class, $response);
    }
}
