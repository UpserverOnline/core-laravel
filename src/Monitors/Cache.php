<?php

namespace UpserverOnline\Core\Monitors;

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Cache as LaravelCache;
use Throwable;

class Cache extends Monitor
{
    const ERROR_CACHE_MISS     = 'cache_miss';
    const ERROR_CONFIG_INVALID = 'config_invalid';
    const ERROR_CONFIG_MISSING = 'config_missing';
    const ERROR_CONNECTION     = 'connection';

    /**
     * Cache store name.
     *
     * @var string
     */
    private $storeName;

    /**
     * @param string $storeName
     */
    public function __construct(string $storeName)
    {
        $this->storeName = $storeName;
    }

    /**
     * Checks the cache store.
     *
     * @return mixed
     */
    public function run()
    {
        if (!$config = $this->config("cache.stores.{$this->storeName}", static::ERROR_CONFIG_MISSING, $this->storeName)) {
            return false;
        }

        try {
            // Try to resolve the store from the manager
            $driver = LaravelCache::driver($this->storeName);
        } catch (Throwable $exception) {
            return $this->error(static::ERROR_CONFIG_INVALID, [
                'message' => $exception->getMessage(),
            ]);
        }

        // Generate a key and value to store
        $key   = static::class . microtime(true);
        $value = Inspiring::quote();

        try {
            // Try to store, retrieve and forget with the cache driver
            $driver->forever($key, $value);
            $valueFromCache = $driver->get($key);
            $driver->forget($key);
        } catch (Throwable $exception) {
            return $this->error(static::ERROR_CONNECTION, [
                'message' => $exception->getMessage(),
            ]);
        }

        if ($valueFromCache !== $value) {
            // The retrieved value is invalid
            $this->error(static::ERROR_CACHE_MISS, [
                'message' => "Cache is not working",
            ]);
        }
    }
}
