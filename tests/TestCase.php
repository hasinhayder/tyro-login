<?php

namespace HasinHayder\TyroLogin\Tests;

use HasinHayder\TyroLogin\Providers\TyroLoginServiceProvider;
use HasinHayder\TyroLogin\Tests\Fixtures\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations manually in setUp
        $this->loadLaravelMigrations(['--database' => 'testing']);
    }

    protected function getPackageProviders($app): array
    {
        return [
            TyroLoginServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set app key
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        // Configure auth to use our test User model
        $app['config']->set('auth.providers.users.model', User::class);

        // Configure tyro-login to use our test User model
        $app['config']->set('tyro-login.user_model', User::class);
    }
}
