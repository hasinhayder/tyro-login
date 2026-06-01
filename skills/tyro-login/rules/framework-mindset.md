# Framework Mindset

**Tier:** 0 — Immutable
**Applies to:** All files
**Cross-references:** [security.md](security.md) (security is a subset of this mindset), [integration-boundaries.md](integration-boundaries.md) (ecosystem thinking)

Foundation rules for thinking like a framework maintainer. These rules are never overridden; they filter down into every technical decision in the package.

---

## Think Like a Framework Maintainer

### Why It Matters

Every line of code in Tyro Login will be consumed by thousands of applications, integrated with unknown User models, running on unknown database configurations, and extended by third-party packages. You do not control the consuming application. You do not control the User model. You do not control the frontend stack. You must assume nothing about the consumer's environment beyond the Laravel version constraint.

Framework code written for one specific use case breaks in unexpected ways for others. The cost of a framework bug is multiplied by every application that depends on it.

### Incorrect

```php
// Assumes User model exists at App\Models\User with specific columns
public function login(Request $request)
{
    $user = App\Models\User::where('email', $request->email)->first();
    // ...
}
```

### Correct

```php
// Uses configurable User model and relies on Laravel's authentication system
public function login(Request $request)
{
    $userModel = config('tyro-login.user_model');
    $user = $userModel::where('email', $request->email)->first();
    // ...
}
```

### Notes

- Never import or reference a concrete User model class.
- Never assume the User model has specific columns beyond `email` and `password`.
- Never assume the User model uses specific traits or implements specific interfaces.
- Every new feature must be gated behind a config toggle with an env var.
- Always design for the consumer who has a completely custom authentication setup.

---

## Protect Public APIs as Contracts

### Why It Matters

In a framework package, the public API is a contract. Once consumers depend on:
- A config key (`tyro-login.features.remember_me`)
- A route name (`tyro-login.login`)
- A method signature (`LoginController@login`)
- A named parameter
- A Blade section or stack
- A published view structure
- A static method

...changing or removing it is a **breaking change** that requires a major version bump. Framework packages do not have the luxury of internal-only refactoring. Every public method, every config key, every route name is a promise to every consuming application.

### Incorrect

```php
// Renaming a route without a deprecation path
Route::get('/sign-in', [LoginController::class, 'showLoginForm'])
    ->name('tyro-login.signin'); // Changed from 'tyro-login.login' — breaks every redirect
```

### Correct

```php
// Deprecate old name, introduce new name, remove in next major version
Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('tyro-login.login');

// In the config, provide route name overrides:
// 'routes' => [
//     'login' => 'login',
// ],
```

### Notes

- Public API includes: config keys, route names, route URIs, method signatures (public and protected), Blade sections, published view file names, command signatures, event classes, mailables.
- Internal API (private methods, internal helpers, unpublished views) can change freely within major versions.
- Document which methods are `@internal` in docblocks.

---

## Feature Flags Before Feature Branches

### Why It Matters

Every new capability added to Tyro Login must be gatable at runtime via config AND env var. This serves three purposes:

1. Consumers can adopt new versions without being forced into new behavior.
2. Consumers can disable features they don't want without forking the package.
3. The package can ship features in a disabled-by-default state for gradual rollouts.

A feature that cannot be disabled via config is a feature that will force an upgrade delay or a fork.

### Incorrect

```php
// New magic login feature — no config toggle, always runs
public function login(Request $request)
{
    // ... existing login logic ...
    // New: always check for magic link
    if ($request->has('magic_token')) {
        // This runs even if the consumer doesn't want magic links
    }
}
```

### Correct

```php
// New feature gated behind config toggle with env var
public function login(Request $request)
{
    if (config('tyro-login.features.magic_links_enabled') && $request->has('magic_token')) {
        // This only runs when the consumer explicitly enables it
    }
}
```

### Notes

- Default new features to `false` (disabled) unless they are security-critical.
- Every config toggle needs a corresponding `TYRO_LOGIN_FEATURE_*` env var.
- Config keys are the source of truth; env vars feed into config, not into controllers.

---

## Prefer Soft Dependencies

### Why It Matters

Requiring a package in `composer.json` forces every consumer to install it, regardless of whether they use the feature. Optional integrations (Tyro role assignment, Socialite providers) should use runtime detection, not hard dependencies.

A hard dependency on an optional package makes Tyro Login heavier, increases conflict risk, and discourages adoption.

### Incorrect

```json
{
    "require": {
        "laravel/socialite": "^5.0"
    }
}
```

### Correct

```json
{
    "require": {
        "illuminate/support": "^12.0||^13.0",
        "illuminate/view": "...",
        "illuminate/routing": "..."
    },
    "suggest": {
        "laravel/socialite": "Required for OAuth social login providers."
    }
}
```

```php
// Runtime detection in controller
public function redirect($provider)
{
    if (! class_exists('Laravel\Socialite\Facades\Socialite')) {
        abort(400, 'Socialite is not installed. Install laravel/socialite to use social login.');
    }
    // ...
}
```

### Notes

- Use `class_exists()` for package detection, `method_exists()` for trait/interface detection.
- Fail gracefully with clear error messages when an optional dependency is missing.
- Log warnings instead of throwing exceptions when optional integrations fail at runtime.

---

## Write for the Person Who Inherits This in 2035

### Why It Matters

Authentication is not a feature — it is infrastructure. This package will be maintained longer than the original author's tenure at any single organization. Code clarity, explicit types, documented rationale, and consistent patterns are not nice-to-haves; they are survival requirements for a package with a 10+ year maintenance horizon.

The most expensive bug in a framework package is the one that requires understanding subtle implicit behavior from 5 years ago.

### Incorrect

```php
// What does this do? Why 15? What's the context?
$items = collect($data)->map(function ($item) {
    return $item * 15;
})->filter()->values();
```

### Correct

```php
// Types are explicit, intent is clear, constants are named
protected const OTP_EXPIRY_MINUTES = 10;
protected const OTP_LENGTH = 6;

protected function generateOtp(): string
{
    $otp = '';
    for ($i = 0; $i < static::OTP_LENGTH; $i++) {
        $otp .= random_int(0, 9);
    }
    return $otp;
}
```

### Notes

- Comments explain *why*, not *what*. The code already says what it does.
- Methods should be under 30 lines. If a method exceeds this, extract helpers.
- Use named constants for every magic number or string.
- Return types and parameter types are mandatory on all public and protected methods.
- Avoid dynamic method calls (`$this->{$method}()`) — they cannot be statically analyzed or refactored.
