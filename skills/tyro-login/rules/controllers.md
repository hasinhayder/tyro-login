# Controllers

**Tier:** 2 — Implementation
**Applies to:** All 6 controllers in `src/Http/Controllers/`, `src/Helpers/InvitationHelper.php`
**Cross-references:** [config-and-env.md](config-and-env.md) (config reads), [routes.md](routes.md) (route names for redirects), [security.md](security.md) (session regeneration, validation, rate limiting), [models-and-casts.md](models-and-casts.md) (user model resolution), [integration-boundaries.md](integration-boundaries.md) (soft-dependency checks in controllers)

Rules for controller structure, authentication flow patterns, validation, and response handling.

---

## Extend `Illuminate\Routing\Controller`, Not Application Base Controller

### Why It Matters

The package's controllers must not depend on `App\Http\Controllers\Controller` because that class does not exist in the package context and may have different base behavior across consuming applications. Extending it would couple the package to each consumer's application structure.

### Incorrect

```php
// Coupled to consumer's application — will not work
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    // ...
}
```

### Correct

```php
// Uses framework base controller — works in any Laravel application
use Illuminate\Routing\Controller;

class LoginController extends Controller
{
    // ...
}
```

### Notes

- Import `Illuminate\Routing\Controller` explicitly. Do not rely on a global namespace.
- All 6 controllers must extend the same base.

---

## Config-Driven Feature Checks Before Any Operation

### Why It Matters

Authentication flows begin with feature checks. Attempting an operation that is disabled via config should redirect the user away from the route, not process the operation and then fail. This ensures disabled features are invisible to users, not just blocked at the final step.

### Incorrect

```php
// Processes the entire login flow before checking if disabled
public function showLoginForm()
{
    return view('tyro-login::login', [
        'layouts' => $this->getLayouts(),
    ]);
    // Consumer disabled password login — should have redirected
}
```

### Correct

```php
// Config check first — redirect before any processing
public function showLoginForm()
{
    if (config('tyro-login.features.disable_password')) {
        return redirect()->route('tyro-login.magic-link.request');
    }

    return view('tyro-login::login', [
        'layouts' => $this->getLayouts(),
    ]);
}
```

### Notes

- Check config at the top of every controller method.
- Redirect to an appropriate alternative route, not a generic 404.
- Use `config('tyro-login.features.*')` for feature toggles.

---

## Use Session for Multi-Step Authentication State

### Why It Matters

Authentication flows like OTP verification and 2FA challenge span multiple requests. The user is partially authenticated — they have provided credentials but have not completed all steps. This state must be stored in the session because the user is not yet fully authenticated and a cache-based token could expire between steps.

### Incorrect

```php
// Cache-based state — can expire mid-flow
public function login(Request $request)
{
    // ... validate credentials ...
    $otpToken = Str::random(40);
    Cache::put('otp:' . $otpToken, $user->id, now()->addMinutes(10));
    return redirect()->route('tyro-login.otp.form', ['token' => $otpToken]);
}
```

### Correct

```php
// Session-based state — persists across the multi-step flow
public function login(Request $request)
{
    // ... validate credentials ...
    session()->put('tyro-login.login.id', $user->id);
    session()->put('tyro-login.login.remember', $request->filled('remember'));
    return redirect()->route('tyro-login.otp.form');
}
```

### Notes

- Use `tyro-login.login.*` session keys to namespace session data.
- Store only the minimum necessary state (user ID, remember preference).
- Clear session state on flow completion or cancellation.

---

## Use Cache for Transient Tokens and Lockout

### Why It Matters

OTP codes, password reset tokens, email verification tokens, magic link hashes, and lockout counters are all transient — they have a TTL and are self-cleaning. Storing them in the database adds unnecessary writes, requires migration management, and requires cleanup jobs. Cache handles TTL expiry natively.

### Incorrect

```php
// Database for transient state — unnecessary migration, needs cleanup
DB::table('password_resets')->insert([
    'email' => $email,
    'token' => $token,
    'created_at' => now(),
]);
```

### Correct

```php
// Cache with TTL — self-cleaning, no database writes
Cache::put(
    'tyro-login.reset:' . $token,
    ['email' => $email],
    now()->addMinutes(config('tyro-login.password_reset.expire', 60))
);
```

### Notes

- Prefix all cache keys with `tyro-login.` to avoid collisions.
- Set TTL from config, not hardcoded.
- Clear cache keys on successful completion (e.g., delete reset token after password is changed).

---

## Protected Helper Methods for Extensibility

### Why It Matters

Subclasses and consuming packages need the ability to override specific behavior without duplicating entire controller methods. Private methods cannot be overridden. Protected methods signal that the method is an extension point.

### Incorrect

```php
// Private — cannot be overridden by extending classes
private function generateCaptcha(): array
{
    // ...
}
```

### Correct

```php
// Protected — extensible by subclasses
protected function generateCaptcha(): array
{
    // ...
}
```

### Notes

- Use `protected` for all helper methods unless there is a specific reason to make them private.
- Methods that are truly internal implementation details with no extension value should still be protected unless they expose internal state.
- Document the method's contract in the docblock: what it accepts, what it returns, when to override.

---

## Type-Declared Return Types on All Methods

### Why It Matters

Controllers in a framework package are consumed directly by routes and by extending classes. Without explicit return types, subclass overrides and static analysis tools cannot verify correctness. An undocumented return type change becomes a breaking change.

### Incorrect

```php
// No return type — PHP defaults to mixed, unverifiable
public function showLoginForm()
{
    return view('tyro-login::login');
}
```

### Correct

```php
// Explicit return type — verifiable, self-documenting
public function showLoginForm(): View
{
    return view('tyro-login::login');
}
```

### Notes

- Form display methods return `Illuminate\View\View`.
- Action methods return `Illuminate\Http\RedirectResponse`.
- Static utilities return their specific type (`string`, `array`, etc.).
- Use `\Illuminate\View\View` and `\Illuminate\Http\RedirectResponse` imports.

---

## Always Validate Before Authentication Operations

### Why It Matters

Validating input before passing it to authentication services prevents unexpected input from reaching the authentication layer. An attacker sending malformed input to the login method should be rejected at validation, not by the authentication system.

### Incorrect

```php
// Authentication attempt before validation
public function login(Request $request)
{
    $credentials = $request->only(['email', 'password']);
    if (Auth::attempt($credentials, $request->filled('remember'))) {
        // ...
    }
}
```

### Correct

```php
// Validate first, then authenticate
public function login(Request $request): RedirectResponse|View
{
    $credentials = $request->validate([
        'email' => ['required', 'string', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (Auth::attempt($credentials, $request->filled('remember'))) {
        return $this->handleSuccessfulAuthentication($request);
    }

    return back()->withErrors(['email' => __('tyro-login::messages.login_failed')]);
}
```

### Notes

- Use `$request->validate()` — not the `Validator` facade directly.
- Split validation rules into a separate method when they become complex (e.g., registration rules).
- Return validation errors to the form with `withErrors()`.

---

## Static Methods Only for Standalone Utilities

### Why It Matters

Static methods in controllers should only exist for standalone utility operations that do not depend on request state, session state, or the current authentication context. They must be pure utility methods that can be called without instantiating the controller.

### Incorrect

```php
// Static method that accesses session — breaks when called statically
public static function generateVerificationUrl($user): string
{
    $prefix = session()->get('locale'); // Session not available in static context
    return URL::signedRoute('tyro-login.verification.verify', [...]);
}
```

### Correct

```php
// Static method — no request/session dependency
public static function generateVerificationUrl(string $userId, string $hash): string
{
    return URL::signedRoute('tyro-login.verification.verify', [
        'id' => $userId,
        'hash' => $hash,
    ]);
}
```

### Notes

- Static methods must not depend on `$this`, `request()`, `session()`, or `auth()`.
- Accept all dependencies as parameters — no implicit resolution.
- Document static methods with `@see` references to the calling context.
