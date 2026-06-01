# Routes

**Tier:** 1 — Structural
**Applies to:** `routes/web.php`
**Cross-references:** [service-provider.md](service-provider.md) (route group wrapping), [controllers.md](controllers.md) (controller method signatures), [security.md](security.md) (POST-only mutations, CSRF, session fixation)

Rules for route definitions, middleware application, naming conventions, and HTTP method discipline.

---

## Named Routes Use the `tyro-login.*` Prefix

### Why It Matters

Route names are part of the public API. Consuming code references them in `route()` and `redirect()->route()` calls. A route named `login` could collide with the consuming application's own routes. The `tyro-login.` prefix ensures all package routes are uniquely namespaced.

### Incorrect

```php
// Route name collision risk — too generic
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login'])->name('login');
```

### Correct

```php
// Namespaced route name — no collision
Route::get('login', [LoginController::class, 'showLoginForm'])->name('tyro-login.login');
Route::post('login', [LoginController::class, 'login'])->name('tyro-login.login');
```

### Notes

- The route group `->name('tyro-login.')` prefix applies automatically to all routes in the group.
- Do not manually prefix every route — rely on the group `name()` method.
- Consuming code calls `route('tyro-login.login')`, not `route('login')`.

---

## Guest vs Auth Middleware Separation

### Why It Matters

Authentication routes accessible to already-logged-in users (login form, register form) must be separated from routes that require authentication (logout, recovery codes, 2FA setup). Mixing them in a single middleware group leads to inconsistent behavior and security issues.

### Incorrect

```php
// All routes in one group — no separation of guest vs auth
Route::middleware(['web'])->group(function () {
    Route::get('login', ...);                        // Guest
    Route::post('login', ...);                       // Guest
    Route::match(['get', 'post'], 'logout', ...);    // Auth
    Route::get('2fa/setup', ...);                    // Both?
});
```

### Correct

```php
// Guest group and auth group — clear separation
Route::middleware(['web'])->prefix(config('tyro-login.routes.prefix', ''))
    ->name('tyro-login.')
    ->group(function () {
        // Guest routes — login, register, password reset, OTP, 2FA challenge
        Route::middleware('guest')->group(function () {
            Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
            Route::post('login', [LoginController::class, 'login']);
            Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
            Route::post('register', [RegisterController::class, 'register']);
            // ... password reset, OTP, 2FA challenge, social auth, magic links
        });

        // Authenticated routes — logout, 2FA management, recovery codes
        Route::middleware('auth')->group(function () {
            Route::match(['get', 'post'], 'logout', [LoginController::class, 'logout'])->name('logout');
            Route::get('2fa/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes'])->name('2fa.recovery-codes');
            Route::post('2fa/setup', [TwoFactorController::class, 'confirm'])->name('2fa.confirm');
            // ... etc
        });
    });
```

### Notes

- Guest routes: login form, login submit, register form, register submit, password reset, OTP verify, 2FA challenge, social auth redirect/callback.
- Auth routes: logout, 2FA setup, 2FA confirm, 2FA skip/ignore, recovery codes display.
- Never use `guest` for routes that modify state (logout, 2FA setup).

---

## Configurable URI Paths

### Why It Matters

Consumers may have existing routes at `/login` or `/register` that conflict with the package defaults. Hardcoded URI paths force consumers to either rename their routes or choose a different prefix that moves all routes away from their preferred paths.

### Incorrect

```php
// Hardcoded URIs — consumer cannot customize individual paths without prefix
Route::get('login', ...)->name('tyro-login.login');
Route::get('register', ...)->name('tyro-login.register');
Route::get('password/reset', ...)->name('tyro-login.password.request');
```

### Correct

```php
// Config-driven URI paths — consumer overrides individual paths
Route::get(config('tyro-login.routes.login', 'login'), ...)->name('tyro-login.login');
Route::get(config('tyro-login.routes.register', 'register'), ...)->name('tyro-login.register');
Route::get(config('tyro-login.routes.password_reset', 'password/reset'), ...)->name('tyro-login.password.request');
```

### Notes

- The config file has a `routes` section with per-route path defaults.
- The optional prefix provides a bulk relocation option; per-route config provides fine-grained control.
- Document every configurable URI path in the config file comments.

---

## POST-Only for State Mutations

### Why It Matters

HTTP GET requests are logged by proxies, cached by browsers, and susceptible to CSRF via `<img>` tags and `<link>` tags. Allowing GET for state mutations (login, logout, register) exposes the application to CSRF attacks and accidental state changes.

### Incorrect

```php
// GET for logout — browser prefetching could log the user out
Route::get('logout', [LoginController::class, 'logout'])->name('tyro-login.logout');
```

### Correct

```php
// POST-only for mutations — GET shows confirmation form or 405
Route::match(['get', 'post'], 'logout', [LoginController::class, 'logout'])->name('tyro-login.logout')
    ->middleware('auth');

// Controller processes only POST
public function logout(Request $request): RedirectResponse
{
    if ($request->isMethod('post')) {
        // ... process logout
    }
    // GET requests show a confirmation page or redirect away
    return redirect()->route('tyro-login.login');
}
```

### Notes

- Login form: GET. Login submit: POST.
- Register form: GET. Register submit: POST.
- Logout: POST only (GET redirects away or shows confirmation).
- OTP verification: POST only.
- 2FA confirmation: POST only.
- Password reset token: POST only.

---

## GET for Form Display, POST for Form Submission

### Why It Matters

Separating form display (GET) from form submission (POST) follows HTTP semantics, enables proper browser caching of form pages, and prevents duplicate form submissions on browser refresh.

### Incorrect

```php
// Single route handles both display and submission — breaks HTTP semantics
Route::post('login', [LoginController::class, 'handle'])->name('tyro-login.login');
```

### Correct

```php
// Separate routes for display and submission
Route::get('login', [LoginController::class, 'showLoginForm'])->name('tyro-login.login');
Route::post('login', [LoginController::class, 'login']);
```

### Notes

- GET routes return `View` responses.
- POST routes return `RedirectResponse` responses.
- GET routes cannot have side effects.
- POST routes always redirect on success, redirect back with errors on failure.
