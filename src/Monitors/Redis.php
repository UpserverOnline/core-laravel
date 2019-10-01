<?php

namespace UpserverOnline\Core\Monitors;

use Illuminate\Support\Str;
use Throwable;

class Redis extends Monitor
{
    const ERROR_CONFIG_INVALID = 'config_invalid';
    const ERROR_CONFIG_MISSING = 'config_missing';
    const ERROR_CONNECTION     = 'connection';

    /**
     * Redis connection name.
     *
     * @var string
     */
    private $connectionName;

    /**
     * @param string $connectionName
     */
    public function __construct(string $connectionName)
    {
        $this->connectionName = $connectionName;
    }

    /**
     * Check the Redis connection.
     *
     * @return mixed
     */
    public function run()
    {
        if (!$config = $this->config("database.redis.{$this->connectionName}", static::ERROR_CONFIG_MISSING, $this->connectionName)) {
            return false;
        }

        $config['timeout'] = $config['read_timeout'] = 5;

        $connectionName = $this->connectionName . Str::random();

        config(["database.redis.{$connectionName}" => $config]);

        try {
            // Try to resolve the connection from the manager
            $connection = app('redis')->connection($connectionName);
        } catch (Throwable $exception) {
            return $this->error(static::ERROR_CONFIG_INVALID, [
                'message' => $exception->getMessage(),
            ]);
        }

        // Run the 'connect' command on the connection
        $connection->connect();

        if (!$connection->isConnected()) {
            // The connection is not connected
            $this->error(static::ERROR_CONNECTION, [
                'message' => "Connection failed",
            ]);
        }
    }
}
