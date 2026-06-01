# OTP

**Tier:** 2 — Implementation
**Applies to:** `src/Http/Controllers/LoginController.php` (OTP-related methods)
**Cross-references:** [security.md](security.md) (random_int, session regeneration), [controllers.md](controllers.md) (session for multi-step state), [config-and-env.md](config-and-env.md) (otp.* config keys), [email-templates.md](email-templates.md) (OTP email delivery)

Rules for one-time password generation, storage, verification, and the resend cooldown mechanism.

---

## Generate OTP Codes with `random_int()`

### Why It Matters

OTP codes are the second factor in authentication. A predictable OTP defeats the purpose of a second factor. `random_int()` uses the operating system's CSPRNG and is cryptographically secure. `rand()` and `mt_rand()` are predictable — given one OTP, an attacker can calculate future OTPs.

### Incorrect

```php
// Predictable — attacker can brute force OTP codes
$otp = rand(100000, 999999);
```

### Correct

```php
// Cryptographically secure — each digit is independently random
protected function generateOtp(): string
{
    $otp = '';
    $length = (int) config('tyro-login.otp.length', 6);
    for ($i = 0; $i < $length; $i++) {
        $otp .= random_int(0, 9);
    }
    return $otp;
}
```

### Notes

- OTP length is config-driven via `tyro-login.otp.length`.
- Each digit is generated independently to avoid modulo bias.
- Never use `mt_rand()`, `rand()`, `shuffle()`, or `time()` for OTP generation.

---

## Store OTP in Cache, Not Database

### Why It Matters

OTP codes are ephemeral — they expire in minutes and are deleted after use. Storing them in the database requires a migration, a cleanup job for expired codes, and adds unnecessary write load to the database. Cache handles TTL expiry natively.

### Incorrect

```php
// Database for transient OTP — unnecessary migration, needs cleanup
DB::table('otp_codes')->insert([
    'user_id' => $user->id,
    'otp' => $otp,
    'expires_at' => now()->addMinutes($expire),
]);
```

### Correct

```php
// Cache with TTL — self-cleaning, no database writes
protected function generateAndSendOtp(Request $request, $user): void
{
    $length = config('tyro-login.otp.length', 4);
    $expire = (int) config('tyro-login.otp.expire', 5);

    $min = (int) (10 ** ($length - 1));
    $max = (int) ((10 ** $length) - 1);
    $otp = (string) random_int($min, $max);

    $cacheKey = $this->getOtpCacheKey($user->id);
    Cache::put($cacheKey, $otp, now()->addMinutes($expire));

    // Send via email if enabled
    if (config('tyro-login.emails.otp.enabled', true)) {
        Mail::to($user->email)->send(new OtpMail($otp, $user->name, $expire));
    }
}

protected function getOtpCacheKey($userId): string
{
    return "tyro-login:otp:{$userId}";
}
```

### Notes

- Cache key pattern: `tyro-login:otp:{userId}`.
- The OTP is stored as a plain string in cache — the cache key is per-user, not per-OTP.
- Delete the cache entry on successful verification or flow cancellation.
- OTP length is config-driven via `tyro-login.otp.length` (default: 4).

---

## Enforce Resend Cooldown via Cache

### Why It Matters

Without a cooldown, an attacker can trigger OTP SMS or email sending repeatedly, causing the user's phone to receive hundreds of messages (sms bombing) or the email provider to rate-limit the sender. A session-based resend tracking mechanism prevents this without adding database writes.

### Incorrect

```php
// No cooldown — attacker can trigger unlimited OTP sends
public function resendOtp(Request $request): RedirectResponse
{
    $otp = $this->generateOtp();
    $this->storeOtp($userId, $otp);
    Mail::to($user)->send(new OtpMail($otp, $user->name, $expire));
    return back()->with('status', 'OTP sent.');
}
```

### Correct

```php
// Session-based resend tracking — enforces cooldown and max resend count
public function resendOtp(Request $request): RedirectResponse
{
    $userId = $request->session()->get('tyro-login.otp.user_id');
    $otpConfig = config('tyro-login.otp');

    $resendCount = $request->session()->get('tyro-login.otp.resend_count', 0);
    $lastResendTime = $request->session()->get('tyro-login.otp.last_resend', 0);
    $cooldown = $otpConfig['resend_cooldown'] ?? 60;

    // Check cooldown
    if ((time() - $lastResendTime) < $cooldown) {
        return redirect()->route('tyro-login.otp.verify')
            ->withErrors(['otp' => 'Please wait before requesting a new code.']);
    }

    // Check max resend attempts
    if ($resendCount >= ($otpConfig['max_resend'] ?? 3)) {
        $request->session()->forget('tyro-login.otp');
        return redirect()->route('tyro-login.login')
            ->withErrors(['email' => 'Maximum resend attempts reached.']);
    }

    $this->generateAndSendOtp($request, $user);

    $request->session()->put('tyro-login.otp.resend_count', $resendCount + 1);
    $request->session()->put('tyro-login.otp.last_resend', time());

    return redirect()->route('tyro-login.otp.verify')
        ->with('success', 'A new verification code has been sent.');
}
```

### Notes

- Resend tracking is stored in session, not cache — it's tied to the user's browser session.
- Session keys: `tyro-login.otp.resend_count` and `tyro-login.otp.last_resend`.
- Config keys: `tyro-login.otp.resend_cooldown` (seconds) and `tyro-login.otp.max_resend`.
- When max resend is reached, clear all OTP session state and redirect to login.
- Display a countdown on the frontend based on the remaining cooldown.

---

## Limit OTP Verification Attempts

### Why It Matters

A 6-digit OTP has 1,000,000 possible combinations. Without attempt limiting, an attacker can brute force the code client-side by sending rapid verification requests. After a configurable number of failed attempts, the OTP should be invalidated and the user must request a new one.

### Incorrect

```php
// Unlimited attempts — attacker brute forces the OTP
public function verifyOtp(Request $request): RedirectResponse
{
    $storedOtp = Cache::get('tyro-login:otp:' . $userId);

    if ($storedOtp === $request->input('otp')) {
        // Log the user in
    }
    // Failed — no attempt tracking
    return back()->withErrors(['otp' => 'Invalid code.']);
}
```

### Correct

```php
// Verify OTP from cache, clear on success or expiry
public function verifyOtp(Request $request): RedirectResponse
{
    if (! $request->session()->has('tyro-login.otp.user_id')) {
        return redirect()->route('tyro-login.login');
    }

    $request->validate(['otp' => ['required', 'string']]);

    $userId = $request->session()->get('tyro-login.otp.user_id');
    $remember = $request->session()->get('tyro-login.otp.remember', false);

    $cacheKey = $this->getOtpCacheKey($userId);
    $storedOtp = Cache::get($cacheKey);

    if (! $storedOtp || $storedOtp !== $request->input('otp')) {
        throw ValidationException::withMessages([
            'otp' => config('tyro-login.otp.error_message', 'Invalid or expired verification code.'),
        ]);
    }

    // OTP valid — clear cache and session
    Cache::forget($cacheKey);
    $request->session()->forget('tyro-login.otp');

    $userModel = config('tyro-login.user_model');
    $user = $userModel::find($userId);

    if (! $user) {
        return redirect()->route('tyro-login.login');
    }

    Auth::login($user, $remember);
    return redirect()->intended(config('tyro-login.redirects.after_login', '/'));
}
```

### Notes

- The OTP is stored as a plain string in cache — compare directly, not as an array.
- Cache key pattern uses `tyro-login:otp:{userId}` (colon separator, matching `getOtpCacheKey()`).
- Session keys use `tyro-login.otp.user_id` and `tyro-login.otp.remember` — the OTP namespace.
- Clear both the cache entry and all OTP session state on successful verification.
- If the user is not found, redirect to login — the session state is already cleared.

---

## Use Session for OTP Flow State

### Why It Matters

The OTP flow is multi-step: login → OTP challenge → fully authenticated. The user's partial authentication state (user ID, whether they checked "remember me") must persist across requests without making the user re-enter credentials. Session state is the correct mechanism because cache tokens can expire mid-flow and URL parameters leak state in logs.

### Incorrect

```php
// URL token for OTP flow state — leaks in logs, can be shared
return redirect()->route('tyro-login.otp.form', ['token' => $urlToken]);
```

### Correct

```php
// Session for partial auth state — persists across OTP flow requests
// Set during login when OTP is required:
Auth::logout();
$request->session()->regenerate();
$request->session()->put('tyro-login.otp.user_id', $userId);
$request->session()->put('tyro-login.otp.remember', $rememberPreference);

// Clear on OTP flow completion:
$request->session()->forget('tyro-login.otp');

// Clear on OTP cancellation:
public function cancelOtp(Request $request): RedirectResponse
{
    if ($request->session()->has('tyro-login.otp.user_id')) {
        $userId = $request->session()->get('tyro-login.otp.user_id');
        Cache::forget($this->getOtpCacheKey($userId));
    }
    $request->session()->forget('tyro-login.otp');
    return redirect()->route('tyro-login.login');
}
```

### Notes

- Session keys namespaced under `tyro-login.otp.*` — distinct from 2FA keys (`login.*`).
- The OTP flow uses `tyro-login.otp.user_id` and `tyro-login.otp.remember`.
- The OTP flow also stores `tyro-login.otp.resend_count` and `tyro-login.otp.last_resend` for resend tracking.
- Clear ALL OTP session data with `$request->session()->forget('tyro-login.otp')` — this removes the entire namespace.
- Before OTP verification, the user is logged out and session regenerated — the OTP session state persists through the regeneration.
- Never store the OTP itself in the session — only store the user identifier.
