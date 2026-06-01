# Integration Boundaries

**Tier:** 3 — Boundary
**Applies to:** `src/Http/Controllers/SocialAuthController.php`, `src/Http/Controllers/RegisterController.php` (Tyro role assignment), `composer.json` (suggest), all `class_exists()` and `method_exists()` checks across `src/`
**Cross-references:** [framework-mindset.md](framework-mindset.md) (soft dependencies philosophy), [service-provider.md](service-provider.md) (auto-discovery), [models-and-casts.md](models-and-casts.md) (user model config), [security.md](security.md) (encrypted token storage for OAuth)

Rules for managing integration boundaries with optional packages (Tyro, Socialite), backward compatibility, and deprecation strategy.

---

## Soft-Check Optional Packages with `class_exists()`

### Why It Matters

Optional packages like `hasinhayder/tyro` (role management) and `laravel/socialite` (OAuth) are listed in `suggest` in `composer.json`, not in `require`. Attempting to use classes from these packages without checking their existence will cause a fatal error if the package is not installed. `class_exists()` with autoloading is the correct runtime check.

### Incorrect

```php
// Hard dependency — crashes if Tyro package is not installed
use HasinHayder\Tyro\Models\Role;

public function assignTyroRole(User $user): void
{
    $role = Role::where('slug', config('tyro-login.tyro.role_slug', 'user'))->first();
    $user->assignRole($role);
}
```

### Correct

```php
// Soft check — gracefully skips when Tyro package is not installed
public function assignTyroRole($user): void
{
    if (! config('tyro-login.tyro.assign_default_role', true)) {
        return;
    }

    if (! class_exists('HasinHayder\\Tyro\\Models\\Role')) {
        return;
    }

    if (! method_exists($user, 'assignRole')) {
        return;
    }

    $roleSlug = config('tyro-login.tyro.default_role_slug', 'user');

    try {
        $roleModel = 'HasinHayder\\Tyro\\Models\\Role';
        $role = $roleModel::where('slug', $roleSlug)->first();

        if ($role) {
            $user->assignRole($role);
        }
    } catch (\Exception $e) {
        report($e);
    }
}
```

### Notes

- Always check `class_exists()` for the package's main class before any integration code.
- Triple check: config toggle (`tyro-login.tyro.assign_default_role`), class existence, method existence.
- Config keys: `tyro-login.tyro.assign_default_role` and `tyro-login.tyro.default_role_slug`.
- Wrap in try/catch — database errors (missing tables) should not break registration.
- Use `report($e)` to report without rethrowing.
- This method exists in both `RegisterController` and `SocialAuthController` — consider extracting to a trait in a future version.

---

## Method Existence Checks for Trait Methods

### Why It Matters

The consuming application's User model may or may not use specific traits that provide methods like `assignRole()`, `hasRole()`, or `isSuspended()`. Calling a method that does not exist on the model causes a fatal error. `method_exists()` at runtime is the correct approach.

### Incorrect

```php
// Assumes the User model has isSuspended() — crashes if it doesn't
public function isUserSuspended($user): bool
{
    return $user->isSuspended();
}
```

### Correct

```php
// Checks method existence — works regardless of User model traits
public function isUserSuspended($user): bool
{
    if (method_exists($user, 'isSuspended')) {
        return $user->isSuspended();
    }

    // Fallback to attribute check for simpler implementations
    if (isset($user->suspended_at)) {
        return ! is_null($user->suspended_at);
    }

    return false;
}
```

### Notes

- Check `method_exists()` for every optional method call.
- Provide a fallback behavior when the method does not exist.
- The fallback should be the safest default (e.g., `false` for `isSuspended()` — not suspended).
- Document which methods are expected on the User model for each integration feature.

---

## Fail Silently on Optional Integrations

### Why It Matters

An optional integration that fails (e.g., Socialite throws an exception, Tyro role assignment fails because of a database connection issue) should not break the primary authentication flow. The integration should be wrapped in try/catch, with the failure logged but not escalated.

### Incorrect

```php
// Integration failure throws — breaks the entire registration flow
public function assignTyroRole($user): void
{
    $role = Role::where('slug', 'user')->first();
    $user->assignRole($role);
}
```

### Correct

```php
// Integration failure is caught and logged — registration continues
public function assignTyroRole($user): void
{
    try {
        if (class_exists('HasinHayder\Tyro\Models\Role') && method_exists($user, 'assignRole')) {
            $role = config('tyro-login.tyro.role_model')::where(
                'slug', config('tyro-login.tyro.role_slug', 'user')
            )->first();

            if ($role) {
                $user->assignRole($role);
            }
        }
    } catch (\Throwable $e) {
        Log::error('Failed to assign Tyro role after registration: ' . $e->getMessage(), [
            'user_id' => $user->id,
            'exception' => $e,
        ]);
    }
}
```

### Notes

- Use `\Throwable` to catch both errors and exceptions.
- Always log the failure with enough context for debugging.
- Never let an optional integration failure prevent the primary auth operation from completing.
- For critical integrations (like an OAuth provider returning invalid data), still validate the data but handle integration infrastructure failures gracefully.

---

## Configuration Key Mapping for Provider Driver Names

### Why It Matters

Socialite provider names do not always match the provider slug used by OAuth providers in the real world. For example, the user-facing provider name "LinkedIn" maps to Socialite's `linkedin-openid` driver, and "Slack" maps to `slack-openid`. A consistent mapping ensures that config keys map to working Socialite drivers.

### Incorrect

```php
// Assumes provider name matches Socialite driver name — breaks for LinkedIn, Slack
public function redirect(string $provider): RedirectResponse
{
    return Socialite::driver($provider)->redirect();
}
```

### Correct

```php
// Explicit driver mapping — handles provider name to Socialite driver mismatches
protected const PROVIDER_DRIVER_MAP = [
    'linkedin' => 'linkedin-openid',
    'slack' => 'slack-openid',
];

public function redirect(string $provider): RedirectResponse
{
    $driver = static::PROVIDER_DRIVER_MAP[$provider] ?? $provider;
    return Socialite::driver($driver)->redirect();
}
```

### Notes

- Maintain the mapping as a constant or config array — not scattered across methods.
- The mapping covers providers where the Socialite driver name differs from the common provider name.
- Log a warning when an unrecognized provider is requested.

---

## Backward Compatibility via Versioned Config Defaults

### Why It Matters

When a new version of Tyro Login changes default behavior (e.g., enabling a new security feature by default, changing a redirect path), existing consumers who upgrade should not experience unexpected behavior changes. Versioned config defaults ensure that new behavior is opt-in for existing consumers and default for new installations.

### Incorrect

```php
// New default applied to all consumers — breaks existing installations
public function login(Request $request): RedirectResponse|View
{
    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        // Old behavior: redirect to intended
        // New behavior: always redirect to dashboard — breaks existing redirects
        return redirect()->route('tyro-login.dashboard');
    }
}
```

### Correct

```php
// Versioned config default — existing consumers keep old behavior
// In config/tyro-login.php:
// 'redirects' => [
//     'after_login' => env('TYRO_LOGIN_AFTER_LOGIN', 'intended'),
// ],

public function login(Request $request): RedirectResponse|View
{
    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();

        $redirect = config('tyro-login.redirects.after_login', 'intended');

        if ($redirect === 'intended') {
            return redirect()->intended(route('tyro-login.dashboard'));
        }

        return redirect()->route($redirect);
    }
}
```

### Notes

- New default behavior must be gated behind a new config key with the old behavior as the default.
- Document in the config file and upgrade guide what changed and at which version.
- Deprecation warnings should appear one major version before a behavior change becomes default.

---

## Deprecation Strategy

### Why It Matters

Deprecating a public API in a framework package requires a systematic process. Abrupt removal breaks consuming applications. A clear deprecation strategy gives consumers time to migrate and provides a clear upgrade path.

### Incorrect

```php
// Method removed without deprecation — breaks all consumers who use it
// Removed in 3.0:
// public function generateVerificationUrl($userId, $hash) { ... }
```

### Correct

```php
// Deprecation process:
// 1. Add @deprecated annotation with the version and replacement
// 2. Add a deprecation notice to the log
// 3. Remove in the next major version

/**
 * Generate the email verification URL.
 *
 * @deprecated 2.5.0 Use VerificationController::generateVerificationUrl() instead.
 * @see \HasinHayder\TyroLogin\Http\Controllers\VerificationController::generateVerificationUrl()
 */
public static function oldGenerateVerificationUrl($userId, $hash): string
{
    Log::deprecated('VerificationHelper::oldGenerateVerificationUrl() is deprecated. Use VerificationController::generateVerificationUrl() instead.');

    return VerificationController::generateVerificationUrl($userId, $hash);
}
```

### Notes

- The deprecation lifecycle is:
  - Major version N: Mark as `@deprecated`, add log notice.
  - Major version N+1: Keep deprecated method, log notice.
  - Major version N+2: Remove.
- Log deprecation warnings at most once per request to avoid log spam.
- Every deprecation must include: the version it was deprecated in, the replacement, and the removal version.

---

## Upgrade Path Documentation

### Why It Matters

Consumers upgrading between major versions need to know exactly what changed, what they need to update, and what the breaking changes are. An undocumented upgrade path forces consumers to diff the entire package or wait for breaking errors in production.

### Incorrect

```php
// Breaking change with no documentation — consumer discovers on deployment
// Changed method signature in 3.0 with no deprecation:
public function login(array $credentials, bool $remember): RedirectResponse|View
```

### Correct

```php
// Breaking change documented in CHANGELOG and UPGRADE guide
// The upgrade guide explains:
// - What changed
// - Why it changed
// - How to migrate
```

The upgrade guide structure:

```
## Upgrading from 2.x to 3.x

### Breaking Changes

1. **LoginController@login method signature**
   - Old: `login(Request $request)`
   - New: `login(array $credentials, bool $remember)`
   - Migration: Controllers extending LoginController must update their method signature.

2. **Route name changes**
   - Old: `tyro-login.otp.form`
   - New: `tyro-login.otp.verify`
   - Migration: Update all `route()` and `redirect()->route()` calls.

### New Features
- 2FA ignore cookie (disabled by default).
- Config: `tyro-login.two_factor.ignore_days`.

### Deprecated (removed in 4.0)
- `VerificationHelper::oldGenerateVerificationUrl()`.
```

### Notes

- Breaking changes require a major version bump (semver).
- Each breaking change must include: the old behavior, the new behavior, and the migration steps.
- Config changes (new keys, changed defaults) must be documented.
- The upgrade guide is a standalone file (`UPGRADE.md`) or a section in `CHANGELOG.md`.
