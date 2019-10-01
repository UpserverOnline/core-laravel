<?php

namespace UpserverOnline\Core;

use Illuminate\Mail\TransportManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;
use UpserverOnline\Core\Monitors\Mail;

class ConfigurationCollector
{
    private static function mailDrivers(): array
    {
        $transportMethods = collect((new ReflectionClass(TransportManager::class))->getMethods())->map->getName();

        $monitorMethods = collect((new ReflectionClass(Mail::class))->getMethods())->map->getName();

        return $transportMethods->filter(function ($methodName) use ($monitorMethods) {
            if (!Str::startsWith($methodName, 'create')) {
                return false;
            }

            if (!Str::endsWith($methodName, 'Driver')) {
                return false;
            }

            $monitorMethod = str_replace(['create', 'Driver'], ['run', 'Check'], $methodName);

            return $monitorMethods->contains($monitorMethod);
        })->map(function ($methodName) {
            return strtolower(substr($methodName, 6, -6));
        })->sort()->values()->all();
    }

    /**
     * Returns an array of the monitoring options for this application.
     *
     * @return array
     */
    public static function get(): array
    {
        return [
            'cache'    => ['stores' => array_keys(config('cache.stores'))],
            'database' => ['connections' => array_keys(config('database.connections'))],
            'mail'     => ['drivers' => self::mailDrivers()],
            'pusher'   => ['connections' => collect(config('broadcasting.connections'))->where('driver', 'pusher')->keys()->all()],
            'queue'    => ['connections' => array_keys(config('queue.connections'))],
            'redis'    => ['connections' => array_keys(Arr::except(config('database.redis'), ['client', 'options']))],
            'storage'  => ['disks' => array_keys(config('filesystems.disks'))],
        ];
    }
}
