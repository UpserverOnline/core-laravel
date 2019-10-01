<?php

namespace UpserverOnline\Core\Tests\Monitors;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Http\Request;
use Illuminate\Queue\Failed\FailedJobProviderInterface as QueueFailer;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UpserverOnline\Core\Controllers\RetryFailedJob;
use UpserverOnline\Core\Tests\TestCase;

class RetryFailedJobTest extends TestCase
{
    private function request()
    {
        $symfonyRequest = SymfonyRequest::create(
            '_upserver/failedJobs/retry', 'POST', [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT'  => 'application/json',
            ], json_encode(['failed_job_id' => 1])
        );

        return Request::createFromBase($symfonyRequest);
    }

    /** @test */
    public function it_responds_with_404_if_the_failed_job_is_not_found()
    {
        $queueFailer = $this->mock(QueueFailer::class)
            ->shouldReceive('find')
            ->with(1)
            ->andReturnNull()
            ->getMock();

        $controller = new RetryFailedJob;

        try {
            $controller($this->request(), $this->mock(QueueFactory::class), $queueFailer);
        } catch (NotFoundHttpException $exception) {
            return $this->assertEquals(404, $exception->getStatusCode());
        }

        $this->fail("Controller should have responded with 404");
    }

    /** @test */
    public function it_should_retry_the_failed_job()
    {
        $queueFailer = $this->mock(QueueFailer::class)
            ->shouldReceive('find')
            ->with(1)
            ->andReturn((object) [
                'id'         => 1,
                'connection' => 'database',
                'queue'      => 'default',
                'payload'    => json_encode(['foo' => 'bar', 'attempts' => 1]),
            ])
            ->getMock();

        $queueFailer->shouldReceive('forget')->with(1);

        $connection = $this->mock(Queue::class)
            ->shouldReceive('pushRaw')
            ->with(json_encode(['foo' => 'bar', 'attempts' => 0, 'retry_of' => 1]), 'default')
            ->getMock();

        $queue = $this->mock(QueueFactory::class)
            ->shouldReceive('connection')
            ->with('database')
            ->andReturn($connection)
            ->getMock();

        $controller = new RetryFailedJob;
        $controller($this->request(), $queue, $queueFailer);
    }
}
