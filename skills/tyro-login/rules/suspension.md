# Suspension

**Tier:** 1 — Structural
**Applies to:** `src/Http/Controllers/LoginController.php` (suspension check), `src/Http/Controllers/SocialAuthController.php` (suspension check on social login)
**Cross-references:** [security.md](security.md) (authentication flow), [controllers.md](controllers.md) (config checks before auth), [integration-boundaries.md](integration-boundaries.md) (method_exists for optional traits)

Rules for user suspension detection, the dual-check pattern for `isSuspended()` and `suspended_at`, and the suspension response.

---

## Check Suspension After Authentication, Before Completing Login

### Why It Matters

A suspended user should not be able to complete the authentication flow regardless of whether their credentials are valid. In Tyro Login's flow, `Auth::attempt()` is called first (which validates credentials), then the session is regenerated, and then the suspension check runs. If suspended, the user is immediately logged out. This pattern works because the user's session is never left in an authenticated state for a suspended user — the logout happens in the same request.

### Incorrect

```php
// No suspension check — suspended user logs in
public function login(Request $request): RedirectResponse|View
{
    if (Auth::attempt($credentials, $request->filled('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended(route('tyro-login.dashboard'));
    }
}
```

### Correct

```php
// Suspension check after Auth::attempt, before completing the login flow
public function login(Request $request): RedirectResponse|View
{
    $credentials = $request->validate([
        'email' => ['required', 'string', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        $this->clearLockout($request);

        $user = Auth::user();

        // Check suspension — logout and reject if suspended
        if ($this->isUserSuspended($user)) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => config('tyro-login.suspension.message', 'Your account has been suspended.'),
            ]);
        }

        // ... continue with 2FA, OTP, email verification checks ...
    }
}
```

### Notes

- The suspension check happens after `Auth::attempt()` and `session()->regenerate()`.
- If suspended, `Auth::logout()` is called immediately in the same request — the session never persists in an authenticated state.
- The suspension message is config-driven via `tyro-login.suspension.message`.
- This pattern applies to: password login, magic link login, and social login (via `handlePostLoginRedirect()`).

---

## Use Dual-Check: Method Existence and Attribute Fallback

### Why It Matters

The consuming application's User model may implement suspension via a custom `isSuspended()` method (from the Tyro package or a custom trait) or via a simple `suspended_at` timestamp column. The package must support both patterns without assuming which one the consumer uses.

### Incorrect

```php
// Assumes isSuspended() method — crashes if User model doesn't have it
public function isUserSuspended($user): bool
{
    return $user->isSuspended();
}
```

### Correct

```php
// Dual check — handles both method and attribute patterns
protected function isUserSuspended($user): bool
{
    // Priority 1: Method-based check (Tyro integration or custom trait)
    if (method_exists($user, 'isSuspended')) {
        return $user->isSuspended();
    }

    // Priority 2: Attribute-based check (simple suspended_at column)
    if (isset($user->suspended_at) && ! is_null($user->suspended_at)) {
        return $user->suspended_at instanceof CarbonInterface
            ? $user->suspended_at->isFuture()
            : true;
    }

    return false;
}
```

### Notes

- `method_exists()` check first because it handles complex suspension logic.
- Attribute fallback for consumers who only have a `suspended_at` column.
- For `suspended_at`, check if the date is in the future (scheduled suspension) or past (active suspension).

---

## Show a Clear Suspension Message Without Revealing Duration

### Why It Matters

A suspended user should know their account is suspended but should not know the exact duration or reason — that information could help an attacker understand the suspension policy and time their attacks accordingly. The message must be generic but actionable.

### Incorrect

```php
// Reveals suspension details — helps attacker time attacks
return redirect()->route('tyro-login.login')
    ->withErrors(['email' => __('Your account has been suspended until March 15, 2026 due to 3 failed attempts.')]);
```

### Correct

```php
// Generic message — user knows to contact support
return redirect()->route('tyro-login.login')
    ->withErrors(['email' => __('Your account has been suspended. Please contact support.')]);
```

### Notes

- The suspension message must not include: duration, reason, or which policy was violated.
- Direct users to contact support — this is the correct escalation path.
- Log the suspension details server-side for support staff to review.

---

## Apply Suspension Check to All Authentication Flows

### Why It Matters

A suspended user bypassing suspension via a different login method (social login, magic link) defeats the purpose of suspension. The suspension check must be applied consistently across every authentication entry point.

### Incorrect

```php
// Suspension checked in password login but not in social login
public function handleSocialUser(...)
{
    $user = $this->createUser($providerUser);
    // No suspension check — suspended user logs in via Google
    Auth::login($user, $remember);
    return redirect()->intended(route('tyro-login.dashboard'));
}
```

### Correct

```php
// Suspension check applied consistently across all flows
public function handleSocialUser(...)
{
    $user = User::where('email', $providerUser->getEmail())->first();

    if ($user && $this->isUserSuspended($user)) {
        return redirect()->route('tyro-login.login')
            ->withErrors(['email' => __('Your account has been suspended. Please contact support.')]);
    }

    // ... create or link social account, then login
}
```

### Notes

- Check suspension in: password login, social login callback, magic link login, OTP verification completion.
- The `isUserSuspended()` helper should be a protected method on a base class or trait to avoid duplication across controllers.
