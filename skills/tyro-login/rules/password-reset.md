# Password Reset

**Tier:** 2 — Implementation
**Applies to:** `src/Http/Controllers/PasswordResetController.php`, `config/tyro-login.php` (password_reset section)
**Cross-references:** [controllers.md](controllers.md) (controller patterns, config checks), [security.md](security.md) (random_int for token generation, cache-based tokens), [mailables.md](mailables.md) (PasswordResetMail), [config-and-env.md](config-and-env.md) (password_reset.* config keys), [password-policy.md](password-policy.md) (password validation for reset)

Rules for the password reset flow — token generation, signed URLs, cache storage, the forgot-password form, the reset form, and security considerations.

---

## Generate Reset Tokens with Cryptographic Randomness

### Why It Matters

A password reset token grants access to reset the user's password. A predictable token allows an attacker to forge a reset link and take over the account. `Str::random(64)` provides sufficient entropy.

### Incorrect

```php
// Predictable token — attacker can guess
$token = md5($user->email . time());
```

### Correct

```php
// Cryptographically secure token
$token = Str::random(64);
```

### Notes

- Same pattern as verification tokens and magic link tokens.
- 64 characters of random data provides adequate entropy against brute force.

---

## Dual Validation: Cache Token + Signed URL

### Why It Matters

Like email verification, password reset uses dual validation: a cache-stored token (ensures single-use and expiration) and a Laravel signed URL (ensures the URL has not been tampered with). Both checks must pass for the reset form to be displayed and the password to be changed.

### Incorrect

```php
// Token-only — URL can be manipulated
$url = url("/password/reset/{$token}");
```

### Correct

```php
// Cache token + signed URL
$expiresInMinutes = (int) config('tyro-login.password_reset.expire', 60);
$expiresAt = now()->addMinutes($expiresInMinutes);

Cache::put("tyro-login:password-reset:{$token}", [
    'user_id' => $user->id,
    'email' => $user->email,
], $expiresAt);

$url = URL::temporarySignedRoute(
    'tyro-login.password.reset',
    $expiresAt,
    ['token' => $token]
);
```

### Notes

- Cache key pattern: `tyro-login:password-reset:{token}`.
- The signed URL and the cache entry share the same expiration time.
- The signed URL validation happens when displaying the reset form AND when processing the reset.

---

## Do Not Reveal Whether a User Exists

### Why It Matters

The forgot-password form accepts an email address and sends a reset link. If the response reveals whether the email exists (different messages for existing vs non-existing users), an attacker can enumerate valid email addresses. The response must be identical regardless of whether the user exists.

### Incorrect

```php
// Reveals user existence — different messages
if ($user) {
    return back()->with('success', 'A reset link has been sent to your email.');
} else {
    return back()->with('error', 'No account found with that email address.');
}
```

### Correct

```php
// Identical response — no user enumeration
$user = $userModel::where('email', $request->email)->first();

if (! $user) {
    return redirect()->route('tyro-login.password.request')
        ->with('success', 'If an account with that email exists, we\'ve sent a password reset link.');
}

// ... generate and send reset link

return redirect()->route('tyro-login.password.request')
    ->with('success', 'If an account with that email exists, we\'ve sent a password reset link.');
```

### Notes

- The success message is identical for existing and non-existing users.
- The conditional phrasing "If an account exists..." provides plausible deniability.
- Always return a successful-looking response — never return an error for unknown emails.

---

## Validate Signature Before Displaying Reset Form

### Why It Matters

The reset form displays the user's email and allows them to set a new password. If the signature is not validated, an attacker could modify the token in the URL and potentially access another user's reset form. The signature check must happen before any form rendering.

### Incorrect

```php
// No signature check — anyone with the token can see the form
public function showResetForm(Request $request, string $token): View
{
    $data = Cache::get("tyro-login:password-reset:{$token}");
    return view('tyro-login::reset-password', ['email' => $data['email']]);
}
```

### Correct

```php
// Signature check first, then token lookup
public function showResetForm(Request $request, string $token): View|RedirectResponse
{
    if (! $request->hasValidSignature()) {
        return redirect()->route('tyro-login.password.request')
            ->with('error', 'The password reset link is invalid or has expired.');
    }

    $data = Cache::get("tyro-login:password-reset:{$token}");

    if (! $data) {
        return redirect()->route('tyro-login.password.request')
            ->with('error', 'The password reset link is invalid or has expired.');
    }

    return view('tyro-login::reset-password', [
        'token' => $token,
        'email' => $data['email'],
    ]);
}
```

### Notes

- Check signature first — fail fast on invalid URLs.
- Use the same error message for both invalid signatures and expired tokens.
- Pass the email from cache (not from user input) to the form for display.

---

## Single-Use Reset Token

### Why It Matters

A password reset token must be consumed after a single use. If the token is not deleted after the password is changed, an attacker who intercepts the URL can reset the password again.

### Incorrect

```php
// Token not deleted — can be reused
$user->password = Hash::make($request->password);
$user->save();
// Token still in cache — attacker can reset again
```

### Correct

```php
// Token deleted after use — single-use
$user->password = Hash::make($request->password);
$user->save();

Cache::forget("tyro-login:password-reset:{$token}");

Auth::login($user);

return redirect(config('tyro-login.redirects.after_login', '/'))
    ->with('success', 'Your password has been reset successfully.');
```

### Notes

- Delete the cache entry immediately after the password is updated.
- Log the user in after a successful reset — they just proved ownership of their email.
- The redirect after reset is config-driven: `tyro-login.redirects.after_login`.

---

## Config-Driven Reset Expiration

### Why It Matters

The reset link expiration window varies by application. Some require 15-minute windows, others allow 24 hours. The expiration must be config-driven so consumers can set their own policy.

### Incorrect

```php
// Hardcoded expiration — consumer cannot customize
$expiresAt = now()->addMinutes(60);
```

### Correct

```php
// Config-driven expiration
$expiresInMinutes = (int) config('tyro-login.password_reset.expire', 60);
$expiresAt = now()->addMinutes($expiresInMinutes);
```

### Notes

- Config key: `tyro-login.password_reset.expire`, defaults to 60 minutes.
- Shorter windows are more secure but may frustrate users on slow email.
- The expiration applies to both the cache TTL and the signed URL.
