# Email Verification

**Tier:** 2 — Implementation
**Applies to:** `src/Http/Controllers/VerificationController.php`, `config/tyro-login.php` (verification section)
**Cross-references:** [controllers.md](controllers.md) (controller patterns, config checks), [security.md](security.md) (random_int for token generation, cache-based tokens), [mailables.md](mailables.md) (VerifyEmailMail), [config-and-env.md](config-and-env.md) (verification.* config keys)

Rules for the email verification flow — token generation, signed URLs, cache storage, verification, and resend logic.

---

## Generate Verification Tokens with Cryptographic Randomness

### Why It Matters

The email verification token must be unpredictable to prevent attackers from guessing verification URLs and confirming email addresses they do not own. `Str::random(64)` provides sufficient entropy for this purpose.

### Incorrect

```php
// Predictable token — md5 of user data is guessable
$token = md5($user->email . $user->id);
```

### Correct

```php
// Cryptographically secure token — 64 random characters
$token = Str::random(64);
```

### Notes

- Use `Str::random(64)` for the token — this is the same pattern used for magic link tokens and password reset tokens.
- The token is stored in cache and used as part of a signed URL.

---

## Dual Verification: Cache Token + Signed URL

### Why It Matters

Email verification uses two layers of validation: a cache-stored token (ensures the token has not expired and is single-use) and a Laravel signed URL (ensures the URL has not been tampered with). Both checks must pass for verification to succeed.

### Incorrect

```php
// Token-only verification — URL can be tampered with (user_id changed)
$url = url("/verify-email?token={$token}&user_id={$user->id}");
```

### Correct

```php
// Cache token + signed URL — tamper-proof and time-limited
$expiresInMinutes = (int) config('tyro-login.verification.expire', 60);
$expiresAt = now()->addMinutes($expiresInMinutes);

Cache::put("tyro-login:email-verify:{$token}", [
    'user_id' => $user->id,
    'email' => $user->email,
], $expiresAt);

$url = URL::temporarySignedRoute(
    'tyro-login.verification.verify',
    $expiresAt,
    ['token' => $token]
);
```

### Notes

- The cache stores the mapping from token to user data.
- The signed URL ensures the URL parameters have not been modified.
- Both the cache entry and the signed URL share the same expiration time.
- Cache key pattern: `tyro-login:email-verify:{token}`.

---

## Store Verification Token in Cache, Not Database

### Why It Matters

Verification tokens are transient — they expire in minutes and are single-use. Storing them in the database requires a migration, a cleanup job for expired tokens, and adds unnecessary write load. Cache handles TTL expiry natively and is self-cleaning.

### Incorrect

```php
// Database for verification tokens — requires migration and cleanup
DB::table('email_verifications')->insert([
    'user_id' => $user->id,
    'token' => $token,
    'created_at' => now(),
]);
```

### Correct

```php
// Cache with TTL — self-cleaning, no database writes
Cache::put(
    "tyro-login:email-verify:{$token}",
    ['user_id' => $user->id, 'email' => $user->email],
    now()->addMinutes(config('tyro-login.verification.expire', 60))
);
```

### Notes

- TTL is config-driven via `tyro-login.verification.expire` (default: 60 minutes).
- Delete the cache entry immediately after successful verification.
- Cache returns `null` when TTL expires — the verification link is automatically invalidated.

---

## Verify Signature Before Token Lookup

### Why It Matters

A signed URL verification (`$request->hasValidSignature()`) must happen before the cache token lookup. Without signature verification, an attacker could modify URL parameters (e.g., changing the token to a different value) and potentially verify a different email.

### Incorrect

```php
// No signature check — URL parameters can be tampered with
public function verify(Request $request, string $token): RedirectResponse
{
    $data = Cache::get("tyro-login:email-verify:{$token}");
    // Token found — but was the URL tampered with?
}
```

### Correct

```php
// Signature check first, then token lookup
public function verify(Request $request, string $token): RedirectResponse
{
    if (! $request->hasValidSignature()) {
        return redirect()->route('tyro-login.login')
            ->with('error', 'The verification link is invalid or has expired.');
    }

    $data = Cache::get("tyro-login:email-verify:{$token}");

    if (! $data) {
        return redirect()->route('tyro-login.login')
            ->with('error', 'The verification link is invalid or has expired.');
    }

    $userModel = config('tyro-login.user_model');
    $user = $userModel::find($data['user_id']);

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    Cache::forget("tyro-login:email-verify:{$token}");
    $request->session()->forget('tyro-login.verification.email');

    return redirect(config('tyro-login.redirects.after_email_verification', '/login'));
}
```

### Notes

- Check signature first — fail fast on invalid URLs.
- Use the same error message for both invalid signatures and expired/missing tokens — do not leak which check failed.
- Clear both the cache token and the session email after verification.
- The redirect after verification is config-driven: `tyro-login.redirects.after_email_verification`.

---

## Static Method for URL Generation

### Why It Matters

The verification URL generation is called from both the `VerificationController` (resend flow) and the `RegisterController` (post-registration). Making it a `public static` method allows cross-controller access without instantiation.

### Incorrect

```php
// Instance method — requires controller instantiation to call from other controllers
public function generateVerificationUrl($user): string
{
    // ...
}
```

### Correct

```php
// Static method — callable from any context
public static function generateVerificationUrl($user, bool $sendEmail = true): string
{
    $token = Str::random(64);
    $expiresInMinutes = (int) config('tyro-login.verification.expire', 60);
    // ... generate URL, store token, send email
    return $url;
}

// Called from RegisterController:
VerificationController::generateVerificationUrl($user);
```

### Notes

- The `$sendEmail` parameter controls whether the email is sent — defaults to `true`.
- The method returns the URL string, making it testable and reusable.
- The method both generates the URL and triggers the email send (when `$sendEmail` is true).

---

## Resend Generates a New Token

### Why It Matters

Resending the verification email must generate a new token, not reuse the old one. The old token may have been compromised (e.g., the email was intercepted). A new token invalidates any old verification links.

### Incorrect

```php
// Resends old token — compromised link is still valid
public function resend(Request $request): RedirectResponse
{
    $oldUrl = Cache::get('tyro-login:email-verify:old');
    Mail::to($user)->send(new VerifyEmailMail($oldUrl));
}
```

### Correct

```php
// Resend generates new token — old token is invalidated naturally
public function resend(Request $request): RedirectResponse
{
    $email = $request->session()->get('tyro-login.verification.email');
    $userModel = config('tyro-login.user_model');
    $user = $userModel::where('email', $email)->first();

    if ($user->hasVerifiedEmail()) {
        return redirect()->route('tyro-login.login');
    }

    $verificationUrl = self::generateVerificationUrl($user);

    return redirect()->route('tyro-login.verification.notice')
        ->with('success', 'A new verification link has been sent to your email address.');
}
```

### Notes

- `generateVerificationUrl()` creates a new token and new cache entry — the old entry expires via TTL.
- Check `hasVerifiedEmail()` before resending — the user may have verified via a different path.
- The email for resend comes from the session key `tyro-login.verification.email`.
