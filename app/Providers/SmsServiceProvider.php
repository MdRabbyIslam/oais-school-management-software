<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Services\Sms\SmsServiceInterface::class,
            function($app) {
                $provider = config('sms.default');
                $class    = config("sms.providers.{$provider}.driver");
                return new $class;
            }
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
