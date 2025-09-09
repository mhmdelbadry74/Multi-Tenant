<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TenantManager;
use App\Services\JwtService;
use App\Services\TenantProvisioner;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantManager::class, function ($app) {
            return new TenantManager($app['config']);
        });

        $this->app->singleton(JwtService::class, function ($app) {
            return new JwtService();
        });

        $this->app->singleton(TenantProvisioner::class, function ($app) {
            return new TenantProvisioner($app->make(TenantManager::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
