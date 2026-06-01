# Config and Environment

**Tier:** 2 — Implementation
**Applies to:** `config/tyro-login.php`, all `config()` and `env()` calls across `src/`
**Cross-references:** [framework-mindset.md](framework-mindset.md) (config-first design), [controllers.md](controllers.md) (controller config reads), [security.md](security.md) (debug config security)

Rules for managing the config file, environment variable protocol, and configuration access patterns across the package.

---

## Every Config Key Needs an Environment Variable

### Why It Matters

Consumers deploy the same code across multiple environments. Hardcoded config values in the published config file prevent per-environment customization. Every config key that lacks an env var forces consumers to either fork the package or manually edit the published config per environment.

### Incorrect

```php
// No env var — consumer must edit config file directly
'expire' => 60,
```

### Correct

```php
// Env var with sensible default — consumer can override per environment
'expire' => (int) env('TYRO_LOGIN_OTP_EXPIRE', 10),
```

### Notes

- Use `(int)` or `(bool)` casting on the env call to ensure correct types.
- Default values must be safe defaults — conservative on security, minimal on assumptions.

---

## Environment Variables Use the `TYRO_LOGIN_` Prefix

### Why It Matters

Without a consistent prefix, env vars collide with other packages and the consuming application. A `DEBUG` env var could be consumed by Laravel, by a debug bar package, by monolog configuration, and by Tyro Login — creating unpredictable behavior.

### Incorrect

```php
// Too generic — likely to collide
'enabled' => env('REGISTRATION_ENABLED', true),
```

### Correct

```php
// Namespaced with package prefix
'enabled' => (bool) env('TYRO_LOGIN_REGISTRATION_ENABLED', true),
```

### Notes

- Exception: `APP_NAME` and `APP_URL` are consumed from Laravel's own env vars, not prefixed.
- All new env vars must follow the `TYRO_LOGIN_` pattern.

---

## Config Is the Source of Truth

### Why It Matters

When controllers call `env()` directly, they bypass the config file's defaults, casting, and documentation. This creates two sources of truth for the same setting. If a consumer publishes and edits the config file, `env()` calls in controllers will silently ignore their changes.

### Incorrect

```php
// Controller reads env directly — bypasses config
public function showLoginForm()
{
    $layout = env('TYRO_LOGIN_LAYOUT', 'centered');
    // ...
}
```

### Correct

```php
// Controller reads from config — config file is the single source of truth
public function showLoginForm()
{
    $layout = config('tyro-login.layout', 'centered');
    // ...
}
```

### Notes

- Only `config/tyro-login.php` may call `env()`.
- Controllers, commands, mailables, and views all call `config('tyro-login.*')`.
- This makes the config file the single documentation point for all settings.

---

## Use Config Arrays for Grouped Settings

### Why It Matters

Flat config keys become unmanageable as the package grows. Grouped settings (providers, email types, social providers) need nested arrays to remain organized and discoverable. Flat keys also make it impossible to iterate over groups.

### Incorrect

```php
// Flat keys — unmanageable at scale
'social_google_enabled' => true,
'social_facebook_enabled' => true,
'social_twitter_enabled' => true,
```

### Correct

```php
// Grouped array — iterable, organized, extensible
'social' => [
    'providers' => [
        'google' => ['enabled' => true, 'client_id' => env('GOOGLE_CLIENT_ID'), ...],
        'facebook' => ['enabled' => false, 'client_id' => env('FACEBOOK_CLIENT_ID'), ...],
    ],
],
```

### Notes

- Use 2-3 levels of nesting maximum — beyond that becomes confusing.
- Document each group with a comment header.
- Group features under clear domain names: `features.*`, `password.*`, `otp.*`.

---

## Cast Environment Values to Correct Types

### Why It Matters

`env()` returns a string or `null`. Config values consumed by controllers, views, and commands expect specific types. An uncast `env('TYRO_LOGIN_DEBUG', false)` returns the string `"false"` which is truthy in PHP — breaking the debug toggle.

### Incorrect

```php
// String "false" is truthy — debug would always be enabled
'debug' => env('TYRO_LOGIN_DEBUG', false),
```

### Correct

```php
// Proper boolean cast — "false" becomes false, "true" becomes true
'debug' => (bool) env('TYRO_LOGIN_DEBUG', false),
```

### Notes

- Use `(bool)` for boolean flags.
- Use `(int)` for numeric values (expiry minutes, lengths, limits).
- Use `(string)` or leave uncast for string values (layout names, URLs).
- Array env vars use JSON encoding: `json_decode(env('TYRO_LOGIN_SOCIAL_PROVIDERS', '[]'), true)`.

---

## Document Every Config Key

### Why It Matters

A config file with 600+ lines and no documentation forces every consumer to read source code to understand what each setting does. This is unacceptable for a framework package. Every config key needs a comment explaining its purpose, valid options, and security implications.

### Incorrect

```php
'lockout' => [
    'max_attempts' => 5,
    'duration' => 60,
],
```

### Correct

```php
/*
|--------------------------------------------------------------------------
| Brute Force Lockout Protection
|--------------------------------------------------------------------------
|
| Controls the number of failed login attempts before the user is locked
| out. The duration is in minutes. Set max_attempts to 0 to disable
| lockout (not recommended for production).
|
| show_attempts_left: When enabled, displays remaining attempts on the
| login form. This helps legitimate users but also helps attackers.
| Disable in production if you want to avoid leaking information.
|
*/
'lockout' => [
    'max_attempts' => (int) env('TYRO_LOGIN_LOCKOUT_MAX_ATTEMPTS', 5),
    'duration' => (int) env('TYRO_LOGIN_LOCKOUT_DURATION', 60),
    'show_attempts_left' => (bool) env('TYRO_LOGIN_SHOW_ATTEMPTS_LEFT', true),
],
```

### Notes

- Document the security implications of each setting (information leakage, performance, etc.).
- Mention valid options for enum-like settings (e.g., `centered`, `split-left`, `split-right`, `fullscreen`, `card`).
- Group-related settings under clearly commented section headers.
