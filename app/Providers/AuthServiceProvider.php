<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Auth\JwtTenantGuard;
use App\Auth\JwtUserProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register JWT Tenant Guard
        Auth::extend('jwt-tenant', function ($app, $name, array $config) {
            return new JwtTenantGuard(
                Auth::createUserProvider($config['provider']),
                $app['request'],
                $app->make(\App\Services\JwtService::class),
                $app->make(\App\Services\TenantManager::class)
            );
        });

        // Register JWT User Provider
        Auth::provider('jwt-tenant-users', function ($app, array $config) {
            return new JwtUserProvider();
        });
    }
}
