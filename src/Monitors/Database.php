<?php

namespace UpserverOnline\Core\Monitors;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PDOException;
use Throwable;

class Database extends Monitor
{
    const ERROR_CONFIG_INVALID = 'config_invalid';
    const ERROR_CONFIG_MISSING = 'config_missing';
    const ERROR_CONNECTION     = 'connection';
    const ERROR_PDO_EXCEPTION  = 'pdo_exception';

    /**
     * Database connection name.
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
     * Check the database connection.
     *
     * @return boolean|null
     */
    public function run()
    {
        if (!$config = $this->config("database.connections.{$this->connectionName}", static::ERROR_CONFIG_MISSING, $this->connectionName)) {
            return false;
        }

        try {
            // Try to resolve the connection from the manager
            $connection = DB::connection($this->connectionName);
        } catch (Throwable $exception) {
            return $this->error(static::ERROR_CONFIG_INVALID, [
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            // Try to get the current read PDO connection
            $connection->getReadPdo();
        } catch (PDOException $exception) {
            $this->error(static::ERROR_PDO_EXCEPTION, [
                'message' => $exception->getMessage(),
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->error(static::ERROR_CONNECTION, [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
