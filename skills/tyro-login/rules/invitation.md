# Invitation and Referral System

**Tier:** 2 — Implementation
**Applies to:** `src/Models/InvitationLink.php`, `src/Models/InvitationReferral.php`, `src/Helpers/InvitationHelper.php`, `src/Http/Controllers/RegisterController.php` (invitation tracking)
**Cross-references:** [models-and-casts.md](models-and-casts.md) (configurable user model in relationships), [integration-boundaries.md](integration-boundaries.md) (soft failure for optional features), [controllers.md](controllers.md) (protected helper methods)

Rules for the invitation link system, referral tracking, self-referral prevention, and the helper pattern.

---

## Invitation Links Are Database-Persisted

### Why It Matters

Unlike OTP codes and magic links which are transient and cache-based, invitation links are persistent — they remain valid indefinitely until explicitly revoked. This is because invitation links are shared by referrers and may be used days or weeks later. Database persistence ensures they survive cache flushes and server restarts.

### Incorrect

```php
// Cache-based invitation — lost on cache flush, unacceptable for referral tracking
Cache::put("tyro-invite:{$hash}", $userId, now()->addDays(30));
```

### Correct

```php
// Database model — persists until explicitly deleted
class InvitationLink extends Model
{
    protected $table = 'invitation_links';

    protected $fillable = ['user_id', 'hash'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('tyro-login.user_model'));
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(InvitationReferral::class, 'invitation_link_id');
    }
}
```

### Notes

- Invitation links use a `hash` field as the unique identifier, not the user ID.
- The `url` accessor generates the registration URL with the hash as a query parameter.
- The `referral_count` accessor provides the count of successful referrals.

---

## Referral Tracking Via InvitationHelper

### Why It Matters

The `InvitationHelper` is a static utility class that encapsulates all invitation and referral logic. Controllers call static methods on this helper rather than implementing referral tracking inline. This keeps the registration controller clean and makes the invitation system independently testable.

### Incorrect

```php
// Inline referral tracking in controller — duplicated logic, hard to test
public function register(Request $request): RedirectResponse
{
    $user = $userModel::create([...]);
    $hash = $request->input('invite');
    if ($hash) {
        $link = InvitationLink::where('hash', $hash)->first();
        if ($link && $link->user_id !== $user->id) {
            $existing = InvitationReferral::where('referred_user_id', $user->id)->first();
            if (! $existing) {
                InvitationReferral::create([
                    'invitation_link_id' => $link->id,
                    'referred_user_id' => $user->id,
                ]);
            }
        }
    }
}
```

### Correct

```php
// Delegated to InvitationHelper — clean controller, testable helper
public function register(Request $request): RedirectResponse
{
    $user = $userModel::create([...]);

    $invitationHash = $request->input('invite') ?? $request->query('invite');
    if ($invitationHash) {
        try {
            InvitationHelper::trackReferral($invitationHash, $user->id);
        } catch (\Exception $e) {
            Log::error('[Tyro-Login] Failed to track invitation referral', [
                'user_id' => $user->id,
                'invitation_hash' => $invitationHash,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

### Notes

- `InvitationHelper` is a static utility — no instance state, no constructor dependencies.
- All methods are `public static` and accept primitive parameters (IDs, hashes), not model instances.
- The helper validates the invitation hash, prevents self-referrals, and prevents duplicate referrals.

---

## Prevent Self-Referrals

### Why It Matters

A user who generates an invitation link and then registers a new account using their own link creates a fraudulent referral. The referral tracking must check that the referrer is not the same user as the new registrant.

### Incorrect

```php
// No self-referral check — user refers themselves
public static function trackReferral(string $hash, int $newUserId): ?InvitationReferral
{
    $link = InvitationLink::where('hash', $hash)->first();
    InvitationReferral::create([
        'invitation_link_id' => $link->id,
        'referred_user_id' => $newUserId,
    ]);
}
```

### Correct

```php
// Self-referral check — prevents gaming the system
public static function trackReferral(?string $invitationHash, int $newUserId): ?InvitationReferral
{
    if (! $invitationHash) {
        return null;
    }

    $invitationLink = self::validateInvitationHash($invitationHash);

    if (! $invitationLink) {
        return null;
    }

    if ($invitationLink->user_id === $newUserId) {
        Log::warning('[Tyro-Login] Self-referral attempt detected', [
            'user_id' => $newUserId,
            'invitation_hash' => $invitationHash,
        ]);
        return null;
    }

    // ... create referral
}
```

### Notes

- Compare `invitationLink->user_id` (the referrer) with `$newUserId` (the referred user).
- Log the self-referral attempt for abuse monitoring.
- Return `null` silently — do not throw an exception that would break registration.

---

## Prevent Duplicate Referrals

### Why It Matters

A user should only be referred once. If a user registers with an invitation link and later someone else shares a different invitation link for the same user, the original referral should stand. Duplicate referrals inflate referral counts and could be exploited.

### Incorrect

```php
// No duplicate check — creates multiple referral records
InvitationReferral::create([
    'invitation_link_id' => $link->id,
    'referred_user_id' => $newUserId,
]);
```

### Correct

```php
// Check for existing referral before creating
$existingReferral = InvitationReferral::where('referred_user_id', $newUserId)->first();
if ($existingReferral) {
    Log::info('[Tyro-Login] User already has a referral record', [
        'user_id' => $newUserId,
        'existing_referral_id' => $existingReferral->id,
    ]);
    return $existingReferral;
}

$referral = InvitationReferral::create([
    'invitation_link_id' => $invitationLink->id,
    'referred_user_id' => $newUserId,
]);
```

### Notes

- Query by `referred_user_id` — each user can only have one referral record.
- Return the existing referral if one exists — do not create a duplicate.
- Log the event for auditing purposes.

---

## Soft Failure for Referral Tracking

### Why It Matters

Referral tracking is an optional feature. A failure in the invitation system (invalid hash, database error, missing table) must never prevent a user from completing registration. The registration flow must always succeed even if referral tracking fails.

### Incorrect

```php
// Unhandled exception — breaks registration if invitation system has issues
InvitationHelper::trackReferral($hash, $user->id);
```

### Correct

```php
// Try/catch with logging — registration always succeeds
try {
    InvitationHelper::trackReferral($invitationHash, $user->id);
} catch (\Exception $e) {
    Log::error('[Tyro-Login] Failed to track invitation referral', [
        'user_id' => $user->id,
        'invitation_hash' => $invitationHash,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}
```

### Notes

- Wrap `InvitationHelper::trackReferral()` in a try/catch in the controller.
- Log the full error context for debugging.
- The helper itself also handles errors gracefully (returns `null`), but the controller adds a second safety net.
- Check both `$request->input('invite')` and `$request->query('invite')` — the hash may come from a hidden form field or a query parameter.

---

## Configurable User Model in Referral Queries

### Why It Matters

The `InvitationHelper::getReferredUsers()` method queries for user models. It must use `config('tyro-login.user_model')` instead of a hardcoded `User` class to support custom user models.

### Incorrect

```php
// Hardcoded User model — breaks with custom user models
public static function getReferredUsers(int $userId)
{
    return User::whereIn('id', $referredUserIds)->get();
}
```

### Correct

```php
// Configurable user model — works with any consumer setup
public static function getReferredUsers(int $userId)
{
    $userModel = config('tyro-login.user_model', 'App\\Models\\User');
    $referredUserIds = $invitationLink->referrals()->pluck('referred_user_id');
    return $userModel::whereIn('id', $referredUserIds)->get();
}
```

### Notes

- Same pattern as all other user model references in the package.
- Default to `'App\\Models\\User'` if config is not set.
