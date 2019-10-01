<?php

namespace UpserverOnline\Core\Controllers;

use Illuminate\Queue\Failed\DatabaseFailedJobProvider;
use Illuminate\Queue\Failed\FailedJobProviderInterface as QueueFailer;

class FailedJobs extends Controller
{
    /**
     * Responds with the failed jobs
     *
     * @param  \Illuminate\Queue\Failed\FailedJobProviderInterface $failer
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(QueueFailer $failer)
    {
        if (!$failer instanceof DatabaseFailedJobProvider) {
            abort(422);
        }

        $config = config('queue.failed');

        return app('db')
            ->connection($config['database'])
            ->table($config['table'])
            ->orderByDesc('failed_at')
            ->paginate();
    }
}
