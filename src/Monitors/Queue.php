<?php

namespace UpserverOnline\Core\Monitors;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use UpserverOnline\Core\Process;

class Queue extends Monitor
{
    const ERROR_CONFIG_INVALID    = 'config_invalid';
    const ERROR_CONFIG_MISSING    = 'config_missing';
    const ERROR_QUEUE_NOT_RUNNING = 'queue_not_running';

    const WARNING_WINDOWS_OS_UNSUPPORTED = 'windows_os_unsupported';

    /**
     * Queue connection name.
     *
     * @var string
     */
    private $connectionName;

    /**
     * Boolean to indicate if this application is running on Windows.
     *
     * @var bool
     */
    private $isWindowsEnvironment;

    /**
     * @param string $connectionName
     */
    public function __construct(string $connectionName)
    {
        $this->connectionName       = $connectionName;
        $this->isWindowsEnvironment = app(Process::class)->isWindowsEnvironment();
    }

    /**
     * Check the queue connection process.
     *
     * @return mixed
     */
    public function run()
    {
        if (!$config = $this->config("queue.connections.{$this->connectionName}", static::ERROR_CONFIG_MISSING, $this->connectionName)) {
            return false;
        }

        if ($this->isWindowsEnvironment) {
            // We do not support Windows environments
            return $this->warning(static::WARNING_WINDOWS_OS_UNSUPPORTED, [
                'message' => "We do not support Windows environments",
            ]);
        }

        if ($this->getQueueProcesses()->isEmpty()) {
            // There is no process running for this queue connection
            return $this->error(static::ERROR_QUEUE_NOT_RUNNING, [
                'message' => "There is no process running for {$this->connectionName}",
            ]);
        }
    }

    /**
     * Returns a collection of all queue processes that run for this application.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getQueueProcesses(): Collection
    {
        return $this->getAllQueueProcesses()->map(function ($row) {
            $pid = explode(' ', $row)[0];

            // Verify that the process is running for this application.
            if ($this->getDirByPid($pid) !== base_path()) {
                return;
            }

            $command = substr($row, strlen($pid) + 1);

            $workerForConnection = config('queue.default');

            // Find out of this process runs for a specific queue
            if (Str::contains($command, '--queue=')) {
                preg_match_all('/(--queue=)([^\s]+)/i', $command, $matches);
                $workerForConnection = $matches[2][0];
            }

            // Verify that the queue is running for the right connection
            if ($this->connectionName !== $workerForConnection) {
                return;
            }

            return [
                'pid'              => $pid,
                'command'          => $command,
                'is_worker'        => Str::contains($command, 'queue:work') && !Str::contains($command, '--daemon'),
                'is_worker_daemon' => Str::contains($command, 'queue:work') && Str::contains($command, '--daemon'),
                'is_listener'      => Str::contains($command, 'queue:listen'),
            ];
        })->filter();
    }

    /**
     * Returns a collection of all processes that contain 'artisan queue:'.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getAllQueueProcesses(): Collection
    {
        if ($this->isWindowsEnvironment) {
            return collect();
        }

        return app(Process::class)->run('exec ps axo pid,command | grep "artisan queue:" | grep -v grep');
    }

    /**
     * Returns the directory of the PID.
     *
     * @param  string|int $pid
     * @return string|null
     */
    private function getDirByPid($pid):  ? string
    {
        return app(Process::class)->run("lsof -p {$pid} | awk '$5 == \"DIR\" { print $9 }'")->first();
    }
}
