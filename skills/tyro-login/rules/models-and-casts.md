# Models and Casts

**Tier:** 1 — Structural
**Applies to:** `src/Models/SocialAccount.php`, `src/Models/InvitationLink.php`, `src/Models/InvitationReferral.php`, `src/Casts/EncryptedOrPlaintext.php`, `src/Traits/HasTwoFactorAuth.php`
**Cross-references:** [security.md](security.md) (encrypted storage), [controllers.md](controllers.md) (user model resolution), [integration-boundaries.md](integration-boundaries.md) (configurable user model)

Rules for Eloquent models, relationships, the custom cast, and the user model trait.

---

## Models Are Thin — No Business Logic

### Why It Matters

Models in a framework package are extended by the consuming application's own models. Business logic in models creates coupling between the package and the consuming application's model layer, making it impossible for consumers to use a different base model or override behavior cleanly.

### Incorrect

```php
// Business logic in model — consumer cannot use a different approach
class SocialAccount extends Model
{
    public function linkUser(User $user): void
    {
        $this->user()->associate($user);
        $this->save();
        // ...
    }
}
```

### Correct

```php
// Thin model — relationships, casts, fillable/hidden only
class SocialAccount extends Model
{
    protected $fillable = [
        'user_id', 'provider', 'provider_user_id', 'provider_email',
        'provider_avatar', 'access_token', 'refresh_token', 'token_expires_at',
    ];

    protected $hidden = [
        'access_token', 'refresh_token',
    ];

    protected $casts = [
        'access_token' => EncryptedOrPlaintext::class,
        'refresh_token' => EncryptedOrPlaintext::class,
        'token_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('tyro-login.user_model'));
    }
}
```

### Notes

- Models only define: `$table`, `$fillable`, `$hidden`, `$casts`, `$timestamps`, relationships.
- Static lookup methods are acceptable when they are simple query wrappers (e.g., `findByProvider()`).
- Complex logic belongs in controllers, helpers, or dedicated service classes.

---

## Configurable User Relationship in Every Model

### Why It Matters

The User model is not fixed. Consuming applications use different namespaces, different model classes, and potentially different databases for users. A hardcoded `User::class` in a model relationship will fail or silently couple the package to a specific user model location.

### Incorrect

```php
// Hardcoded User model — breaks if consumer uses a different model
public function user(): BelongsTo
{
    return $this->belongsTo(App\Models\User::class);
}
```

### Correct

```php
// Config-driven User model — works with any consumer setup
public function user(): BelongsTo
{
    return $this->belongsTo(config('tyro-login.user_model'));
}
```

### Notes

- Every `belongsTo()`, `hasMany()`, and `morphTo()` that references the user must use the config key.
- The config default is `'App\\Models\\User'`.
- Never import a concrete User model class in any model file.

---

## Encrypted Secrets via `EncryptedOrPlaintext` Cast

### Why It Matters

OAuth tokens and TOTP secrets stored in plaintext in the database are accessible to anyone with database read access — developers, DBAs, attackers with SQL injection. Encryption at rest ensures that database compromises do not expose authentication tokens.

`EncryptedOrPlaintext` handles the transition from unencrypted legacy data (stored before this cast existed) to encrypted data (always written by the cast). This allows consumers to upgrade without a data migration script.

### Incorrect

```php
// Plaintext storage — tokens readable from database
protected $casts = [
    'access_token' => 'string',
    'refresh_token' => 'string',
];
```

### Correct

```php
// Encrypted storage — legacy data readable, new data encrypted
protected $casts = [
    'access_token' => EncryptedOrPlaintext::class,
    'refresh_token' => EncryptedOrPlaintext::class,
    'two_factor_secret' => EncryptedOrPlaintext::class,
    'two_factor_recovery_codes' => EncryptedOrPlaintext::class,
];
```

### Notes

- Always use `EncryptedOrPlaintext` for: OAuth access tokens, OAuth refresh tokens, TOTP secrets, recovery codes.
- The cast uses `Crypt::encryptString()` on write and tries `Crypt::decryptString()` on read with plaintext fallback.
- Never store unencrypted tokens at rest — this is a security regression.

---

## Trait Provides User Model Extension Without Inheritance

### Why It Matters

The consuming application's User model already extends `Illuminate\Foundation\Auth\User` (or `Authenticatable`). The package cannot use inheritance to add features to the User model. A trait is the correct mechanism — it merges casts and provides methods without requiring a change to the model's inheritance chain.

### Incorrect

```php
// Forcing inheritance change — consumer must restructure their User model
abstract class TyroLoginUser extends Authenticatable
{
    // Consumer must extend this instead of Authenticatable directly
}
```

### Correct

```php
// Trait — consumer adds to their existing User model
trait HasTwoFactorAuth
{
    public function initializeHasTwoFactorAuth(): void
    {
        $this->mergeCasts([
            'two_factor_secret' => EncryptedOrPlaintext::class,
            'two_factor_recovery_codes' => EncryptedOrPlaintext::class,
            'two_factor_confirmed_at' => 'datetime',
        ]);

        $this->mergeHidden([
            'two_factor_secret',
            'two_factor_recovery_codes',
        ]);
    }

    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    public function recoveryCodes(): array
    {
        return json_decode(
            decrypt($this->two_factor_recovery_codes) ?? '[]',
            true
        );
    }
}
```

### Notes

- Use `initialize{TraitName}()` for trait boot logic — this is called automatically by Eloquent.
- Use `mergeCasts()` and `mergeHidden()` to avoid overwriting the consumer's existing casts and hidden attributes.
- Never use `$casts = [...]` directly in a trait — this overwrites the model's own `$casts` property.

---

## Custom Cast for Migration Safety

### Why It Matters

When a package introduces encryption for previously unencrypted columns, existing data in consumer databases is still in plaintext. Reading encrypted columns with `Crypt::decryptString()` on plaintext data throws a `DecryptException`. A custom cast that gracefully falls back to plaintext on read prevents this error.

### Incorrect

```php
// Always encrypts — but what about legacy plaintext data?
public function get($model, $key, $value, $attributes)
{
    return Crypt::decryptString($value);
}
```

### Correct

```php
// Falls back to plaintext for legacy data, encrypts on write
public function get($model, $key, $value, $attributes): ?string
{
    if (is_null($value)) {
        return null;
    }

    try {
        return Crypt::decryptString($value);
    } catch (DecryptException $e) {
        // Legacy plaintext data — will be encrypted on next write
        return $value;
    }
}

public function set($model, $key, $value, $attributes): ?string
{
    if (is_null($value)) {
        return null;
    }

    return Crypt::encryptString($value);
}
```

### Notes

- The `get()` method must return the raw value on failure, not throw.
- The `set()` method always encrypts — no fallback.
- Consumers can optionally run a data migration to re-save all records and transition them to encrypted storage.
