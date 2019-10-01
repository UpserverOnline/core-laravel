<?php

namespace UpserverOnline\Core;

use Illuminate\Support\Collection;
use Symfony\Component\Process\Process as SymfonyProcess;

class Process
{
    /**
     * Runs the given command and returns the output as a Collection of lines.
     *
     * @param  string $command
     * @return \Illuminate\Support\Collection
     */
    public function run(string $command): Collection
    {
        $process = new SymfonyProcess([], null, ['COLUMNS' => '2000'], null, 60);

        $process->setCommandLine($command)->run();

        $output = $process->getOutput();

        return collect(explode("\n", trim($output)));
    }

    /**
     * Alias for the Laravel helper 'windows_os'.
     *
     * @return bool
     */
    public function isWindowsEnvironment(): bool
    {
        return windows_os();
    }
}
