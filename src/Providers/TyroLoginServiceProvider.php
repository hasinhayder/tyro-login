<?php

namespace HasinHayder\TyroLogin\Providers;

use HasinHayder\TyroLogin\Console\Commands\DocCommand;
use HasinHayder\TyroLogin\Console\Commands\InstallCommand;
use HasinHayder\TyroLogin\Console\Commands\PublishCommand;
use HasinHayder\TyroLogin\Console\Commands\StarCommand;
use HasinHayder\TyroLogin\Console\Commands\VersionCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TyroLoginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/tyro-login.php', 'tyro-login');
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerCommands();
        $this->registerMigrations();
    }

    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => config('tyro-login.routes.prefix', ''),
            'middleware' => config('tyro-login.routes.middleware', ['web']),
            'as' => 'tyro-login.',
        ], function (): void {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        });
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'tyro-login');
    }

    protected function registerMigrations(): void
    {
        // Only load migrations if social login is enabled
        if (config('tyro-login.social.enabled', false)) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }
    }

    protected function registerPublishing(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/tyro-login.php' => config_path('tyro-login.php'),
        ], 'tyro-login-config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/tyro-login'),
        ], 'tyro-login-views');

        // Publish email templates only
        $this->publishes([
            __DIR__ . '/../../resources/views/emails' => resource_path('views/vendor/tyro-login/emails'),
        ], 'tyro-login-emails');

        // Publish assets
        $this->publishes([
            __DIR__ . '/../../resources/assets' => public_path('vendor/tyro-login'),
        ], 'tyro-login-assets');

        // Publish migrations (for social login)
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'tyro-login-migrations');

        // Publish all
        $this->publishes([
            __DIR__ . '/../../config/tyro-login.php' => config_path('tyro-login.php'),
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/tyro-login'),
            __DIR__ . '/../../resources/assets' => public_path('vendor/tyro-login'),
        ], 'tyro-login');
    }

    protected function registerCommands(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
            PublishCommand::class,
            VersionCommand::class,
            DocCommand::class,
            StarCommand::class,
        ]);
    }
}
