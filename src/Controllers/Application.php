<?php

namespace UpserverOnline\Core\Controllers;

use UpserverOnline\Core\Api;

class Application extends Controller
{
    /**
     * Responds with the configured application ID.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke()
    {
        return $this->data([
            'app_id' => config('upserver.app_id'),

            'upserver_package_version' => Api::PACKAGE_VERSION,
        ]);
    }
}
