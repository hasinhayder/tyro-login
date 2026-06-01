# Magic Login

**Tier:** 2 — Implementation
**Applies to:** `src/Http/Controllers/LoginController.php` (magic link methods)
**Cross-references:** [security.md](security.md) (random_int for token generation, session regeneration), [controllers.md](controllers.md) (session for multi-step state), [suspension.md](suspension.md) (suspension check), [two-factor.md](two-factor.md) (2FA challenge after magic link)

Rules for passwordless magic link login flow — token generation, cache storage, link validation, and post-login flow.

---

## Generate Magic Link Tokens with Cryptographic Randomness

### Why It Matters

A magic link token is a password replacement — anyone with the link can authenticate as the user. Predictable token generation defeats the entire purpose of passwordless login. The token must be generated from a cryptographically secure random source with sufficient entropy to prevent brute forcing.

### Incorrect

```php
// Predictable token — attacker can brute force or guess
$token = md5($user->email . now()->toDateTimeString());
```

### Correct

```php
// Cryptographically secure token — sufficient entropy
protected function generateMagicToken(): string
{
    return Str::random(64);
}
```

### Notes

- `Str::random(64)` provides 64 bytes of random data, encoded as ~86 alphanumeric characters.
- The token is stored hashed in cache — never store the raw token for lookup.
- Validate the token length before lookup to prevent very long inputs from reaching the cache.

---

## Store Magic Link Tokens in Cache with Short TTL

### Why It Matters

Magic links are single-use and time-limited. Storing them in the database requires a migration, cleanup jobs, and adds unnecessary write load. Cache handles TTL expiry natively, and the token is naturally deleted after use or expiry.

### Incorrect

```php
// Database for magic link tokens — requires migration and cleanup
DB::table('magic_links')->insert([
    'user_id' => $user->id,
    'token' => hash('sha256', $token),
    'expires_at' => now()->addMinutes($expire),
]);
```

### Correct

```php
// Cache with short TTL — self-cleaning, single-use
protected function requestMagicLink(Request $request): RedirectResponse
{
    $hash = Str::random(32);
    $expiresInMinutes = (int) config('tyro-login.emails.magic_link.expire', 5);

    $data = [
        'hash' => $hash,
        'user_id' => $user->id,
        'expires_at' => now()->addMinutes($expiresInMinutes)->timestamp,
        'created_at' => now()->timestamp,
        'used' => false,
        'ip' => null,
    ];

    Cache::put("tyro_magic_link_{$hash}", $data, now()->addMinutes($expiresInMinutes));

    // Track in index for admin management
    $index = Cache::get('tyro_magic_links_index', []);
    $index[] = $hash;
    Cache::forever('tyro_magic_links_index', array_unique($index));
}
```

### Notes

- Cache key pattern: `tyro_magic_link_{hash}` — the raw hash is the cache key (no hashing of the hash).
- The stored data includes: `hash`, `user_id`, `expires_at`, `created_at`, `used` flag, and `ip`.
- TTL is config-driven via `tyro-login.emails.magic_link.expire` (default: 5 minutes).
- An index (`tyro_magic_links_index`) tracks all active hashes for admin management commands.
- **Note:** The cache key prefix `tyro_magic_link_` differs from the package-wide convention `tyro-login:` used by OTP, verification, and password reset. This is a known inconsistency in the codebase — do not introduce new cache keys using this pattern.
- Mark the token as consumed by updating `used` to `true` after use, and record the consumer's IP.

---

## Prevent Replay Attacks with Single-Use Tokens

### Why It Matters

If a magic link is intercepted (via email compromise, man-in-the-middle, or log inspection), the attacker should not be able to use the link again. The token must be invalidated immediately after the first successful use, even if the login flow is not yet complete.

### Incorrect

```php
// No replay protection — link can be used multiple times
public function magicLogin(string $token): RedirectResponse
{
    $stored = Cache::get('tyro_magic_link_' . $hash);
    if (! $stored) {
        return redirect()->route('tyro-login.login')
            ->withErrors(['email' => __('Invalid or expired link.')]);
    }

    // Token still in cache — attacker can replay
    Auth::loginUsingId($stored['user_id']);
    return redirect()->intended(route('tyro-login.dashboard'));
}
```

### Correct

```php
// Mark as used before authentication
public function magicLogin(Request $request): RedirectResponse
{
    $hash = $request->input('hash');
    $data = Cache::get("tyro_magic_link_{$hash}");

    if (! $data) {
        return redirect()->route('tyro-login.login')
            ->withErrors(['login' => 'Invalid or expired magic link.']);
    }

    if ($data['used']) {
        return redirect()->route('tyro-login.login')
            ->withErrors(['login' => 'This magic link has already been used.']);
    }

    $user = (config('tyro-login.user_model'))::find($data['user_id']);

    if (! $user || $this->isUserSuspended($user)) {
        return redirect()->route('tyro-login.login')
            ->withErrors(['login' => 'Unable to log in with this link.']);
    }

    // Mark as used — prevent replay
    $data['used'] = true;
    $data['ip'] = $request->ip();
    $expiresAt = Carbon::createFromTimestamp($data['expires_at']);
    Cache::put("tyro_magic_link_{$hash}", $data, $expiresAt);

    $request->session()->regenerate();
    Auth::login($user);

    // ... continue with 2FA checks
}
```

### Notes

- Mark `used` to `true` and record the consumer's IP before proceeding with authentication.
- The error message for "already used" is distinct from "invalid" — this helps users understand what happened.
- Also check for expired links — cache returns null if TTL has expired.
- Suspension check happens after marking as used but before completing login.

---

## Apply All Post-Authentication Checks After Magic Link

### Why It Matters

A magic link bypasses the password authentication step but must not bypass other security checks (suspension, 2FA, email verification). Every check that applies after password login also applies after magic link login.

### Incorrect

```php
// Direct login — skips suspension and 2FA checks
public function magicLogin(string $token): RedirectResponse
{
    $stored = Cache::get('tyro_magic_link_' . $hash);
    Auth::loginUsingId($stored['user_id']);
    return redirect()->intended(route('tyro-login.dashboard'));
}
```

### Correct

```php
// Full auth pipeline — suspension check, 2FA challenge, session regeneration
public function magicLogin(Request $request): RedirectResponse
{
    // ... validate hash and mark as used ...

    Auth::login($user);

    if ($this->isUserSuspended($user)) {
        Auth::logout();
        return redirect()->route('tyro-login.login')
            ->withErrors(['login' => config('tyro-login.suspension.message')]);
    }

    if (config('tyro-login.two_factor.enabled', false)) {
        if (filled($user->two_factor_confirmed_at)) {
            Auth::logout();
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', false);
            return redirect()->route('tyro-login.two-factor.challenge');
        }
        // ... check 2FA setup / skip / ignore ...
    }

    return redirect()->intended(config('tyro-login.redirects.after_login', '/'));
}
```

### Notes

- Magic link flows must check: suspension, 2FA (if enabled).
- Session keys for 2FA: `login.id` and `login.remember` (shared with password login and OTP flows).
- Session regeneration happens at the start of the magic link flow.
- 2FA setup skip/ignore cookie checks follow the same pattern as the password login flow.

---

## Feature-Gate Magic Links Behind Config

### Why It Matters

Magic links are an alternative to password-based login. Some applications want only password login, some want only magic links, and some want both. The feature must be gateable via config and env var, defaulting to disabled for security-conscious applications.

### Incorrect

```php
// Always enabled — consumer cannot disable magic links
public function showLoginForm(): View
{
    return view('tyro-login::login', [
        'magicLinksEnabled' => true,
        // ...
    ]);
}
```

### Correct

```php
// Config-driven toggle — consumer enables explicitly
public function showLoginForm(): View
{
    return view('tyro-login::login', [
        'magicLinksEnabled' => config('tyro-login.features.magic_links_enabled'),
        'passwordDisabled' => config('tyro-login.features.disable_password'),
        // ...
    ]);
}
```

### Notes

- Config key: `tyro-login.features.magic_links_enabled`, env var: `TYRO_LOGIN_FEATURE_MAGIC_LINKS`.
- Default to `false` — magic links increase the attack surface (email compromise = account compromise).
- When `disable_password` is `true` and magic links are `true`, the login form shows only the magic link option.
