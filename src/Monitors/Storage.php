<?php

namespace UpserverOnline\Core\Monitors;

use Illuminate\Support\Facades\Storage as LaravelStorage;
use Throwable;

class Storage extends Monitor
{
    const ERROR_CACHE_MISS     = 'cache_miss';
    const ERROR_CONFIG_INVALID = 'config_invalid';
    const ERROR_CONFIG_MISSING = 'config_missing';
    const ERROR_CONNECTION     = 'connection';

    /**
     * Storage disk name.
     *
     * @var string
     */
    private $diskName;

    /**
     * @param string $diskName
     */
    public function __construct(string $diskName)
    {
        $this->diskName = $diskName;
    }

    /**
     * Check the storage disk.
     *
     * @return mixed
     */
    public function run()
    {
        if (!$config = $this->config("filesystems.disks.{$this->diskName}", static::ERROR_CONFIG_MISSING, $this->diskName)) {
            return false;
        }

        try {
            // Try to resolve the disk from the manager
            $driver = LaravelStorage::disk($this->diskName);
        } catch (Throwable $exception) {
            return $this->error(static::ERROR_CONFIG_INVALID, [
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            // Try to get all the files from the root directory
            $driver->files();
        } catch (Throwable $exception) {
            return $this->error(static::ERROR_CONNECTION, [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
