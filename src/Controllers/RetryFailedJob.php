<?php

namespace UpserverOnline\Core\Controllers;

use Illuminate\Contracts\Queue\Factory as Queue;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Queue\Failed\FailedJobProviderInterface as QueueFailer;

class RetryFailedJob extends Controller
{
    use ValidatesRequests;

    /**
     * Responds with the failed jobs
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, Queue $queue, QueueFailer $failer)
    {
        $data = $this->validate($request, [
            'failed_job_id' => 'required',
        ]);

        $job = $failer->find($data['failed_job_id']);

        abort_unless($job, 404);

        $queue->connection($job->connection)->pushRaw(
            $this->resetAttempts($job), $job->queue
        );

        $failer->forget($job->id);

        return response()->json();
    }

    /**
     * Reset the payload attempts.
     *
     * Applicable to Redis jobs which store attempts in their payload.
     *
     * @param  object  $job
     * @return string
     */
    protected function resetAttempts($job)
    {
        $payload = json_decode($job->payload, true);

        $payload['retry_of'] = $job->id;

        if (isset($payload['attempts'])) {
            $payload['attempts'] = 0;
        }

        return json_encode($payload);
    }
}
