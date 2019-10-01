<?php

namespace UpserverOnline\Core;

use Illuminate\Support\Str;

class Support
{
    /**
     * Compares the Laravel app version.
     *
     * @param  string $version
     * @param  string $operator
     * @return bool
     */
    public static function whereAppVersion(string $operator, string $version): bool
    {
        return version_compare(app()->version(), $version, $operator);
    }

    /**
     * Returns a boolean wether the SparkPost mail driver is supported.
     * See also: https://laravel.com/docs/6.0/upgrade
     *
     * @return bool
     */
    public static function supportsSparkPostDriver(): bool
    {
        return static::whereAppVersion('<', '6.0.0');
    }

    /**
     * Returns a boolean wether the Mandrill mail driver is supported.
     * See also: https://laravel.com/docs/6.0/upgrade
     *
     * @return bool
     */
    public static function supportsMandrillDriver(): bool
    {
        return static::whereAppVersion('<', '6.0.0');
    }

    /**
     * Returns a boolean wether the Postmark mail driver is supported.
     * See also: https://laracasts.com/series/whats-new-in-laravel-5-8/episodes/3
     *
     * @return bool
     */
    public static function supportsPostmarkDriver(): bool
    {
        return static::whereAppVersion('>=', '5.8.0');
    }

    /**
     * Returns a boolean wether event caching is supported.
     * See also: https://github.com/laravel/framework/releases/tag/v5.8.9
     *
     * @return bool
     */
    public static function supportsEventCaching(): bool
    {
        return static::whereAppVersion('>=', '5.8.9');
    }
}
