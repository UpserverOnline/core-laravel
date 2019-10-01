<?php

namespace UpserverOnline\Core\Monitors;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use ReflectionClass;
use ReflectionException;

class Horizon extends Monitor
{
    const ERROR_HORIZON_NOT_INSTALLED = 'horizon_not_installed';
    const ERROR_HORIZON_NOT_RUNNING   = 'horizon_not_running';

    /**
     * Checks if one or more Horizon master suporvisors are running.
     *
     * @return boolean|null
     */
    public function run()
    {
        try {
            new ReflectionClass(MasterSupervisorRepository::class);
        } catch (ReflectionException $exception) {
            return $this->error(static::ERROR_HORIZON_NOT_INSTALLED, [
                'message' => 'Horizon is not installed',
            ]);
        }

        $masterSupervisorRepository = app(MasterSupervisorRepository::class);

        if (empty($masterSupervisorRepository->all())) {
            $this->error(static::ERROR_HORIZON_NOT_RUNNING, [
                'message' => 'None of the Horizon master suporvisors are running',
            ]);
        }
    }
}
