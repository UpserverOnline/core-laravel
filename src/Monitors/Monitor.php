<?php

namespace UpserverOnline\Core\Monitors;

abstract class Monitor
{
    /**
     * The collected errors.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * The collected warnings.
     *
     * @var array
     */
    protected $warnings = [];

    /**
     * The check token from the Upserver.online service.
     *
     * @var string
     */
    protected $checkToken;

    /**
     * Adds an error.
     *
     * @param  string     $type
     * @param  array|null $data
     *
     * @return bool
     */
    protected function error(string $type, array $data = null): bool
    {
        $this->errors[$type][] = ['data' => $data];

        return false;
    }

    /**
     * Returns if any errors were collected, can filter by key.
     *
     * @param  string|null $key
     * @return bool
     */
    public function hasError(string $key = null): bool
    {
        if (!is_null($key)) {
            return array_key_exists($key, $this->errors());
        }

        return !empty($this->errors());
    }

    /**
     * Returns all errors.
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Adds a warning.
     *
     * @param  string     $type
     * @param  array|null $data
     *
     * @return bool
     */
    protected function warning(string $type, array $data = null): bool
    {
        $this->warnings[$type][] = ['data' => $data];

        return false;
    }

    /**
     * Returns if any warnings were collected, can filter by key.
     *
     * @param  string|null $key
     * @return bool
     */
    public function hasWarning(string $key = null): bool
    {
        if (!is_null($key)) {
            return array_key_exists($key, $this->warnings());
        }

        return !empty($this->warnings());
    }

    /**
     * Returns all warnings.
     *
     * @return array
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * Resets the 'errors' and 'warnings' arrays, run the monitor and
     * returns a boolean wether an error occured.
     *
     * @return bool
     */
    public function passes(): bool
    {
        $this->errors   = [];
        $this->warnings = [];

        $this->run();

        return !$this->hasError();
    }

    /**
     * Setter for the check token
     *
     * @param string $token
     */
    public function setCheckToken(string $token): self
    {
        $this->checkToken = $token;

        return $this;
    }

    /**
     * Returns the config by key or adds an error.
     *
     * @param  string $key
     * @param  string $errorKey
     * @param  string $configName
     * @return array|bool
     */
    protected function config($key, $errorKey, $configName)
    {
        $config = config($key);

        if (is_array($config)) {
            return $config;
        }

        // No config to resolve the store
        return $this->error($errorKey, [
            'message' => "The configuration for {$configName} is missing",
        ]);
    }

    /**
     * The method that actually runs the monitor.
     *
     * @return void
     */
    abstract public function run();
}
