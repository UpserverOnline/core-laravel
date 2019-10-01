<?php

namespace UpserverOnline\Core\Monitors;

use UpserverOnline\Core\Support;

class Config extends Monitor
{
    const ERROR_APP_DEBUG                         = 'app_debug';
    const ERROR_BOOTSTRAP_CACHE_PATH_NOT_WRITABLE = 'bootstrap_cache_path_not_writable';
    const ERROR_STORAGE_PATH_NOT_WRITABLE         = 'storage_path_not_writable';

    const WARNING_CONFIG_NOT_CACHED = 'config_not_cached';
    const WARNING_EVENTS_NOT_CACHED = 'events_not_cached';
    const WARNING_ROUTES_NOT_CACHED = 'routes_not_cached';

    /**
     * Checks the configuration of the app.
     *
     * @return mixed
     */
    public function run()
    {
        $this->checkDebug();

        $this->checkStoragePath();

        $this->checkBootstrapCachePath();

        $this->checkConfigurationCaching();

        $this->checkRouteCaching();

        $this->checkEventCaching();
    }

    /**
     * Checks if debugging is enabled.
     *
     * @return bool|null
     */
    protected function checkDebug()
    {
        if (!config('app.debug')) {
            return;
        }

        $this->error(static::ERROR_APP_DEBUG, [
            'message' => 'The application runs in debug modus',
        ]);
    }

    /**
     * Checks if the storage path is writable.
     *
     * @return bool|null
     */
    protected function checkStoragePath()
    {
        if (is_writable(storage_path())) {
            return;
        }

        $this->error(static::ERROR_STORAGE_PATH_NOT_WRITABLE, [
            'message' => 'The storage path is not writable',
        ]);
    }

    /**
     * Checks if the bootstrap cache path is writable.
     *
     * @return bool|null
     */
    protected function checkBootstrapCachePath()
    {
        if (is_writable(base_path('bootstrap/cache'))) {
            return;
        }

        $this->error(static::ERROR_BOOTSTRAP_CACHE_PATH_NOT_WRITABLE, [
            'message' => 'The bootstrap cache path is not writable',
        ]);
    }

    /**
     * Checks if the configuration is cached.
     *
     * @return bool|null
     */
    protected function checkConfigurationCaching()
    {
        if (app()->configurationIsCached()) {
            return;
        }

        $this->warning(static::WARNING_CONFIG_NOT_CACHED, [
            'message' => 'The application config is not cached',
        ]);
    }

    /**
     * Checks if the routes are cached.
     *
     * @return bool|null
     */
    protected function checkRouteCaching()
    {
        if (app()->routesAreCached()) {
            return;
        }

        $this->warning(static::WARNING_ROUTES_NOT_CACHED, [
            'message' => 'The application routes are not cached',
        ]);
    }

    /**
     * Checks if the events are cached.
     *
     * @return bool|null
     */
    protected function checkEventCaching()
    {
        if (!Support::supportsEventCaching()) {
            return;
        }

        if (app()->eventsAreCached()) {
            return;
        }

        $this->warning(static::WARNING_EVENTS_NOT_CACHED, [
            'message' => 'The application events are not cached',
        ]);
    }
}
