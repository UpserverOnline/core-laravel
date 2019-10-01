<?php

namespace UpserverOnline\Core\Monitors;

use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Throwable;

class Pusher extends Monitor
{
    const ERROR_PUSHER_NOT_INSTALLED = 'pusher_not_installed';
    const ERROR_CONFIG_INVALID       = 'config_invalid';
    const ERROR_CONFIG_MISSING       = 'config_missing';
    const ERROR_CONNECTION           = 'connection';
    const ERROR_FETCH_CHANNELS       = 'fetch_channels';
    const ERROR_NON_PUSHER_DRIVER    = 'non_pusher_driver';

    /**
     * Broadcasting connection name.
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
     * Checks the broadcasting connection.
     *
     * @return mixed
     */
    public function run()
    {
        if (!$config = $this->config("broadcasting.connections.{$this->connectionName}", static::ERROR_CONFIG_MISSING, $this->connectionName)) {
            return false;
        }

        $driver = $config['driver'] ?? null;

        if ($driver !== 'pusher') {
            // We only support Pusher drivers
            return $this->error(static::ERROR_NON_PUSHER_DRIVER, [
                'message' => "The driver for {$this->connectionName} is not pusher",
            ]);
        }

        try {
            // Try to resolve the connection from the manager
            $connection = app(BroadcastingFactory::class)->connection($this->connectionName);
        } catch (Throwable $exception) {
            return $this->error(static::ERROR_CONFIG_INVALID, [
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            // Try to fetch the channels from the Pusher instance
            $channels = $connection->getPusher()->get_channels();
        } catch (Throwable $exception) {
            return $this->error(static::ERROR_CONNECTION, [
                'message' => $exception->getMessage(),
            ]);
        }

        if ($channels === false) {
            // The 'get_channels' method returns an array when fetched successfully
            return $this->error(static::ERROR_FETCH_CHANNELS, [
                'message' => 'Could not fetch the channels',
            ]);
        }
    }
}
