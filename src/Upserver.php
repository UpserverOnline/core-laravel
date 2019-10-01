<?php

namespace UpserverOnline\Core;

use Illuminate\Support\Facades\Facade;

class Upserver extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'upserver-online.api';
    }
}
