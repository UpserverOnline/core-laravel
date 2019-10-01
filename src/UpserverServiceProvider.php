<?php

namespace UpserverOnline\Core;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use UpserverOnline\Core\Api;
use UpserverOnline\Core\Commands\VerifyConfig;
use UpserverOnline\Core\Listeners\CaptureFailedJob;

class UpserverServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/upserver.php' => config_path('upserver.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                VerifyConfig::class,
            ]);
        }

        if (config('upserver.failed_jobs')) {
            $this->app['events']->listen(JobFailed::class, CaptureFailedJob::class);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/upserver.php', 'upserver');

        $this->app->singleton('upserver-online.api-client', function ($app) {
            return new HttpClient([
                'http_errors' => false,
            ]);
        });

        $this->app->singleton('upserver-online.api', function ($app) {
            return new Api(
                app('upserver-online.api-client'),
                config('upserver.app_id') ?: '',
                config('upserver.app_token') ?: '',
                config('upserver.endpoint')
            );
        });

        $this->app->singleton('upserver-online.monitors', function ($app) {
            return [
                'broadcasting' => Monitors\Pusher::class,
                'cache'        => Monitors\Cache::class,
                'composer'     => Monitors\Composer::class,
                'config'       => Monitors\Config::class,
                'database'     => Monitors\Database::class,
                'horizon'      => Monitors\Horizon::class,
                'mail'         => Monitors\Mail::class,
                'queue'        => Monitors\Queue::class,
                'redis'        => Monitors\Redis::class,
                'storage'      => Monitors\Storage::class,
            ];
        });

        $this->registerRoutes();
    }

    protected function registerRoutes()
    {
        Route::prefix('_upserver')->group(function () {
            Route::middleware(Middleware::class)->group(function () {
                Route::get('application', Controllers\Application::class);
                Route::post('check', Controllers\Check::class);
                Route::get('configuration', Controllers\Configuration::class);
                Route::get('failedJobs', Controllers\FailedJobs::class);
                Route::post('failedJobs/retry', Controllers\RetryFailedJob::class);
            });
        });
    }

}
