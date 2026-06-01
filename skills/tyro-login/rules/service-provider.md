# Service Provider

**Tier:** 1 — Structural
**Applies to:** `src/Providers/TyroLoginServiceProvider.php`, `composer.json` (extra section)
**Cross-references:** [config-and-env.md](config-and-env.md) (config merge), [routes.md](routes.md) (route loading), [views-and-themes.md](views-and-themes.md) (view loading), [commands.md](commands.md) (command registration), [integration-boundaries.md](integration-boundaries.md) (composer.json structure)

Rules for the package service provider — the bootstrap entrypoint for the entire package.

---

## `register()` Only Merges Config

### Why It Matters

The `register()` method runs before all other service providers and before the application is fully booted. Performing boot logic (loading routes, registering views, publishing assets) in `register()` causes unpredictable behavior because the necessary application services may not yet be available.

### Incorrect

```php
// Boot logic in register() — too early, risks undefined services
public function register(): void
{
    $this->mergeConfigFrom(__DIR__ . '/../../config/tyro-login.php', 'tyro-login');
    $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php'); // Too early for routing
}
```

### Correct

```php
// register() only merges config — nothing else
public function register(): void
{
    $this->mergeConfigFrom(__DIR__ . '/../../config/tyro-login.php', 'tyro-login');
}

// All boot logic in boot()
public function boot(): void
{
    $this->registerRoutes();
    $this->registerViews();
    $this->registerMigrations();
    $this->registerPublishing();
    $this->registerCommands();
    $this->configureAuthRedirection();
}
```

### Notes

- `mergeConfigFrom()` allows the consumer's config to override package defaults.
- Never bind classes or register singletons in `register()` unless they are required for other providers.

---

## `boot()` Follows a Strict Order

### Why It Matters

The order of operations in `boot()` matters. Routes must be registered before they can be referenced. Views and publishing must be registered before they can be used. An inconsistent order across versions makes upgrading unpredictable for consumers.

### Incorrect

```php
// Arbitrary order — publishing before routes, commands mixed in
public function boot(): void
{
    $this->registerPublishing();
    $this->registerCommands();
    $this->registerRoutes();
    $this->registerViews();
}
```

### Correct

```php
// Strict order: publishing → routes → views → commands → migrations → auth
public function boot(): void
{
    $this->registerRoutes();
    $this->registerViews();
    $this->registerMigrations();
    $this->registerPublishing();
    $this->registerCommands();
    $this->configureAuthRedirection();
}
```

### Notes

- Routes first — everything else may reference route names.
- Views next — published views must be discoverable.
- Migrations after views — migrations depend on the config being available.
- Publishing after migrations — consumers should know what tables exist before publishing.
- Commands last — commands depend on all other components being available.
- Auth redirection last — the middleware needs all routes to be registered.

---

## Uses Named Publish Tags

### Why It Matters

Consumers need fine-grained control over what they publish. A single `--tag=tyro-login` publishing everything forces consumers to either publish everything or manually copy files. Named tags let consumers publish only what they need — config only, views only, emails only.

### Incorrect

```php
// Single tag — consumer must publish everything
$this->publishes([
    __DIR__ . '/../../config/tyro-login.php' => config_path('tyro-login.php'),
    __DIR__ . '/../../resources/views' => resource_path('views/vendor/tyro-login'),
], 'tyro-login');
```

### Correct

```php
// Named tags — consumer publishes exactly what they need
$this->publishes([
    __DIR__ . '/../../config/tyro-login.php' => config_path('tyro-login.php'),
], 'tyro-login-config');

$this->publishes([
    __DIR__ . '/../../resources/views/auth' => resource_path('views/vendor/tyro-login/auth'),
], 'tyro-login-views');

$this->publishes([
    __DIR__ . '/../../resources/views/emails' => resource_path('views/vendor/tyro-login/emails'),
], 'tyro-login-emails');

// Plus an "all" tag for convenience
$this->publishes([...], 'tyro-login');
```

### Notes

- Tag names: `tyro-login-config`, `tyro-login-views`, `tyro-login-emails`, `tyro-login-styles`, `tyro-login-theme`, `tyro-login-assets`, `tyro-login-migrations`.
- Provide an `all` tag that publishes everything.
- Each publish call is separate so tags are independently invocable.

---

## Route Group Wraps All Routes

### Why It Matters

Without a wrapping route group, every route is individually defined with middleware, prefix, and name — creating duplication and making it impossible to change the route configuration centrally. A route group ensures consistent middleware, prefix, and naming across all auth routes.

### Incorrect

```php
// Each route individually defined — duplication, inconsistent
Route::middleware(['web'])->prefix(config('tyro-login.routes.prefix'))->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
});
// No group for the rest — forgot password, OTP, 2FA are scattered
```

### Correct

```php
// Single route group wrapping all auth routes
Route::middleware(['web'])
    ->prefix(config('tyro-login.routes.prefix', ''))
    ->name('tyro-login.')
    ->group(function () {
        // Guest routes
        Route::middleware('guest')->group(function () {
            Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
            Route::post('login', [LoginController::class, 'login']);
            // ... all other guest routes
        });

        // Authenticated routes
        Route::middleware('auth')->group(function () {
            Route::match(['get', 'post'], 'logout', [LoginController::class, 'logout'])->name('logout');
            // ... recovery codes, 2FA management
        });
    });
```

### Notes

- The prefix is config-driven: `config('tyro-login.routes.prefix', '')`.
- The name prefix must be `tyro-login.` — not `login.` or `auth.`.
- Middleware groups are `web` for the outer group, `guest` and `auth` for inner groups.
- Guest and auth routes must be in separate inner groups.

---

## Auto-Discovery via `composer.json` Extra

### Why It Matters

Consumers should not have to manually add the service provider to their `config/app.php` providers array. Laravel's package auto-discovery handles this automatically when the provider is listed in `composer.json` `extra.laravel.providers`.

### Incorrect

```json
{
    "name": "hasinhayder/tyro-login",
    "extra": {}
}
```

### Correct

```json
{
    "name": "hasinhayder/tyro-login",
    "extra": {
        "laravel": {
            "providers": [
                "HasinHayder\\TyroLogin\\Providers\\TyroLoginServiceProvider"
            ]
        }
    }
}
```

### Notes

- The provider class must be fully qualified with double backslashes.
- If the consumer has disabled auto-discovery for this package, they add the provider manually.
- Never register facades or aliases via auto-discovery — Tyro Login does not expose facades.
