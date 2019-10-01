<?php

namespace UpserverOnline\Core\Controllers;

use UpserverOnline\Core\ConfigurationCollector;

class Configuration extends Controller
{
    /**
     * Responds with the monitoring options for this application.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke()
    {
        return $this->data(ConfigurationCollector::get());
    }
}
