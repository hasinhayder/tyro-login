# Security

**Tier:** 0 — Immutable
**Applies to:** All controllers, `EncryptedOrPlaintext` cast, `HasTwoFactorAuth` trait, `SocialAccount` model, config debug settings, session handling across all files
**Cross-references:** [controllers.md](controllers.md) (validation, session handling), [routes.md](routes.md) (POST-only mutations), [models-and-casts.md](models-and-casts.md) (encrypted storage), [config-and-env.md](config-and-env.md) (debug config, lockout config)

Security rules are Tier 0 — they override all other rules when in conflict. These are non-negotiable for an authentication framework package.

---

## Use `random_int()` for All Security-Sensitive Generation

### Why It Matters

OTP codes, password reset tokens, magic link hashes, and email verification tokens must be cryptographically unpredictable. Functions like `rand()`, `mt_rand()`, and `shuffle()` are predictable — given one output, an attacker can calculate all future outputs. `random_int()` uses the operating system's CSPRNG and is unpredictable.

### Incorrect

```php
// Predictable — attacker can brute force OTP codes
$otp = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
```

### Correct

```php
// Unpredictable — cryptographically secure
protected function generateOtp(): string
{
    $otp = '';
    for ($i = 0; $i < (int) config('tyro-login.otp.length', 6); $i++) {
        $otp .= random_int(0, 9);
    }
    return $otp;
}
```

### Notes

- `random_int()` is the only acceptable random number generator for authentication contexts.
- Acceptable exceptions: `Str::random()` for non-security use (UI display, nonces not used for auth).
- Never use `time()` or `microtime()` as a randomization source.

---

## Regenerate Session on Every Authentication Transition

### Why It Matters

Session fixation attacks work by forcing a known session ID onto a victim. When a user transitions between authentication states (anonymous → authenticated, authenticated → step-up, authenticated → anonymous), regenerating the session ID invalidates any attacker-forced session ID.

### Incorrect

```php
// No session regeneration — vulnerable to session fixation
public function login(Request $request): RedirectResponse
{
    if (Auth::attempt($credentials, $request->filled('remember'))) {
        return redirect()->intended(route('tyro-login.dashboard'));
    }
}
```

### Correct

```php
// Session regeneration on every auth transition
public function login(Request $request): RedirectResponse|View
{
    if (Auth::attempt($credentials, $request->filled('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended(route('tyro-login.dashboard'));
    }
}

public function logout(Request $request): RedirectResponse
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('tyro-login.login');
}
```

### Notes

- Regenerate on: login, logout, OTP verification, 2FA step-up authentication.
- Call `session()->regenerate()` after authentication, not before.
- For logout, call `session()->invalidate()` which both clears and regenerates.
- Always call `regenerateToken()` to rotate the CSRF token on logout.

---

## POST-Only with CSRF for All State Mutations

### Why It Matters

Laravel applies the CSRF token check only to POST, PUT, PATCH, and DELETE routes. GET requests are not CSRF-protected. Allowing GET for logout, login, or any other state mutation means an attacker can craft a link like `<img src="https://example.com/logout">` that executes the action when the image URL is loaded.

### Incorrect

```php
// GET logout — no CSRF protection, vulnerable to img-src CSRF
Route::get('logout', [LoginController::class, 'logout'])->name('tyro-login.logout');

public function logout(): RedirectResponse
{
    Auth::logout();
    return redirect()->route('tyro-login.login');
}
```

### Correct

```php
// POST-only logout — Laravel's CSRF middleware protects it
Route::match(['get', 'post'], 'logout', [LoginController::class, 'logout'])->name('tyro-login.logout');

public function logout(Request $request): RedirectResponse
{
    if ($request->isMethod('post')) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('tyro-login.login');
    }

    // GET requests redirect away — no state change
    return redirect()->route('tyro-login.login');
}
```

### Notes

- Every `POST` route in the route file automatically has CSRF protection via the `web` middleware group.
- Do not disable CSRF middleware for any package route.
- If a consumer needs an API authentication flow, they should use Laravel Sanctum or Passport — not the package's web routes.

---

## Encrypt Tokens and Secrets at Rest

### Why It Matters

OAuth access tokens (which can grant API access on behalf of the user), OAuth refresh tokens (which can generate new access tokens), and TOTP secrets (which can generate valid 2FA codes) must be encrypted at rest. A database breach that exposes these tokens compromises every user's accounts and authentication.

### Incorrect

```php
// Plaintext storage — database breach exposes all tokens
$socialAccount->access_token = $token->accessToken;
$socialAccount->refresh_token = $token->refreshToken;
$socialAccount->save();
```

### Correct

```php
// Encrypted via EncryptedOrPlaintext cast — configured in model
class SocialAccount extends Model
{
    protected $casts = [
        'access_token' => EncryptedOrPlaintext::class,
        'refresh_token' => EncryptedOrPlaintext::class,
    ];
}

// The set() method of the cast encrypts automatically
$socialAccount->access_token = $token->accessToken; // Encrypted on save
$socialAccount->refresh_token = $token->refreshToken; // Encrypted on save
$socialAccount->save();
```

### Notes

- Always encrypt: OAuth access tokens, OAuth refresh tokens, TOTP secrets, TOTP recovery codes.
- Use the `EncryptedOrPlaintext` cast — it includes the legacy plaintext fallback.
- Never log or dump token values — tokens must be handled programmatically only.

---

## Mask Emails in Logs

### Why It Matters

Email addresses are personally identifiable information (PII). Logging full email addresses in debug logs, error logs, or any output visible to developers creates privacy risk, potential GDPR/CCPA compliance issues, and exposes user identity information in contexts that may not be adequately protected.

### Incorrect

```php
// Full email in logs — PII exposure
Log::info('Password reset requested for: ' . $email);
```

### Correct

```php
// Masked email — first 3 characters visible, rest masked
Log::info('Password reset requested for: ' . Str::mask($email, '*', 3));
```

### Notes

- Apply `Str::mask()` to any email before logging, reporting, or error output.
- The debug config (`config('tyro-login.debug')`) must be explicitly enabled before any PII can be logged unmasked.
- All log output in controllers, commands, and helpers must mask emails.

---

## Cache-Based Lockout with Configurable Policy

### Why It Matters

Brute-force attacks against authentication endpoints attempt many passwords for a single user or a single password for many users. Without lockout, an attacker can attempt unlimited passwords. Database-based lockout requires additional migrations and cleanup jobs. Cache-based lockout is self-cleaning via TTL.

### Incorrect

```php
// No lockout — unlimited authentication attempts
public function login(Request $request): RedirectResponse|View
{
    $credentials = $request->validate([...]);

    if (Auth::attempt($credentials, $request->filled('remember'))) {
        return $this->handleSuccessfulAuthentication($request);
    }

    return back()->withErrors(['email' => __('Invalid credentials.')]);
}
```

### Correct

```php
// Cache-based lockout — configurable attempts and duration
protected function isLockedOut(Request $request): bool
{
    return Cache::has('tyro-login.lockout:' . $request->ip());
}

protected function shouldLockout(Request $request): bool
{
    $key = 'tyro-login.attempts:' . $request->ip();
    $attempts = (int) Cache::get($key, 0);
    return $attempts >= (int) config('tyro-login.lockout.max_attempts', 5);
}

protected function incrementAttempts(Request $request): void
{
    $key = 'tyro-login.attempts:' . $request->ip();
    Cache::add($key, 0, now()->addMinutes(config('tyro-login.lockout.duration', 60)));
    Cache::increment($key);
}

protected function clearAttempts(Request $request): void
{
    Cache::forget('tyro-login.attempts:' . $request->ip());
}
```

### Notes

- Key lockout by IP address — user-based keying leaks whether an email exists in the system.
- Config is driven by `tyro-login.lockout.*` settings.
- Show remaining attempts only when `show_attempts_left` config is enabled.
- Clear lockout on successful authentication.

---

## Privacy-Safe Debug Mode

### Why It Matters

Debug logging in an authentication package can expose sensitive data (passwords, tokens, session IDs, IP addresses) to logs, which are often collected by centralized logging systems accessible to non-security teams. Debug must be opt-in and clearly documented as a security risk.

### Incorrect

```php
// Debug logging always active — sensitive data in production logs
Log::info('Login attempt', [
    'email' => $email,
    'password' => $request->input('password'), // Password in logs
    'ip' => $request->ip(),
]);
```

### Correct

```php
// Debug logging gated behind config — only when explicitly enabled
if (config('tyro-login.debug')) {
    Log::info('Login attempt', [
        'email' => Str::mask($email, '*', 3),
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'timestamp' => now()->toIso8601String(),
    ]);
}

// Never log passwords — even in debug mode
```

### Notes

- The `debug` config defaults to `false`.
- The config file contains a security warning in the debug section documentation.
- Even in debug mode, passwords must never be logged.
- Even in debug mode, full emails must be masked.

---

## Validate Before Any Authentication Operation

### Why It Matters

Passing unvalidated input to `Auth::attempt()` or any authentication check can result in type errors, unexpected behavior, or database queries with malformed input. Validation is the first line of defense — reject malformed input before it reaches any authentication logic.

### Incorrect

```php
// No validation — malformed input reaches the authentication layer
public function login(Request $request): RedirectResponse|View
{
    if (Auth::attempt($request->only(['email', 'password']), $request->has('remember'))) {
        return $this->handleSuccessfulAuthentication($request);
    }

    return back()->withErrors(['email' => __('Invalid credentials.')]);
}
```

### Correct

```php
// Validate first — malformed input is rejected before authentication
public function login(Request $request): RedirectResponse|View
{
    $credentials = $request->validate([
        'email' => ['required', 'string', 'max:255'],
        'password' => ['required', 'string'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended(route('tyro-login.dashboard'));
    }

    $this->incrementAttempts($request);

    if ($this->shouldLockout($request)) {
        $this->lockoutUser($request);
        return redirect()->route('tyro-login.lockout');
    }

    return back()->withErrors(['email' => __('Invalid credentials.')]);
}
```

### Notes

- Every controller action that accepts user input must call `$request->validate()` at the start.
- Validation rules should be extracted to a protected method when they are complex (e.g., registration password rules).
- Validation errors are returned with `withErrors()` — never redirect to a generic error page.
