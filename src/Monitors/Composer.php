<?php

namespace UpserverOnline\Core\Monitors;

use Illuminate\Support\Str;
use UpserverOnline\Core\ApiResponse;
use UpserverOnline\Core\Upserver;

class Composer extends Monitor
{
    const ERROR_VULNERABILITIES_FOUND = 'vulnerabilities_found';

    /**
     * Checks if composer.lock contains vulnerabilities.
     *
     * @return null|boolean
     */
    public function run()
    {
        $path = base_path('composer.lock');

        $lockContents = json_decode(file_get_contents($path), true);

        $scanData = tap(Upserver::composer($this->checkToken, $lockContents), function (ApiResponse $response) {
            abort_unless($response->isSuccessful(), $response->statusCode());
        })->get('data');

        if (!$scanData['count']) {
            return;
        }

        return $this->error(static::ERROR_VULNERABILITIES_FOUND, [
            'message'         => "Found {$scanData['count']} " . Str::plural('vulnerability', $scanData['count']),
            'vulnerabilities' => $scanData['vulnerabilities'],
        ]);
    }
}
