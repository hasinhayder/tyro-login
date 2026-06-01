# Commands

**Tier:** 2 — Implementation
**Applies to:** All 13 Artisan commands in `src/Console/Commands/`
**Cross-references:** [config-and-env.md](config-and-env.md) (config reads in commands), [models-and-casts.md](models-and-casts.md) (user model resolution), [integration-boundaries.md](integration-boundaries.md) (feature-gated commands)

Rules for Artisan command signatures, user lookup, interactive prompts, output conventions, and feature gating.

---

## All Signatures Use the `tyro-login:` Prefix

### Why It Matters

Artisan commands without a namespace prefix collide with commands from other packages and from the consuming application. A command named `user:verify` could be a Tyro Login command, a Spark command, or a custom application command. The `tyro-login:` prefix eliminates ambiguity.

### Incorrect

```php
// Unprefixed — collision risk with other packages
protected $signature = 'user:verify {identifier}';
```

### Correct

```php
// Pakage-prefixed — no collision
protected $signature = 'tyro-login:verify-user {identifier}';
```

### Notes

- Signature pattern: `tyro-login:{verb}-{noun}` (e.g., `tyro-login:reset-2fa`, `tyro-login:setup-2fa`).
- The `tyro-login:` prefix is part of the public API — do not change it without a major version bump.
- All 13 commands follow this pattern.

---

## Interactive Prompts for Destructive Operations

### Why It Matters

Commands that flush, reset, or delete data (flush magic links, flush invitation links, reset 2FA) can destroy user data irreversibly. Running these commands in production without confirmation is dangerous. An interactive `confirm()` prompt prevents accidental destructive operations.

### Incorrect

```php
// No confirmation — destructive operation runs silently
public function handle(): void
{
    Cache::forget('tyro-login.magic-links:*');
    $this->info('All magic links have been flushed.');
}
```

### Correct

```php
// Confirmation required — prevents accidental data loss
public function handle(): void
{
    if (! $this->confirm('Are you sure you want to flush all magic links? This action cannot be undone.')) {
        $this->info('Operation cancelled.');
        return;
    }

    Cache::forget('tyro-login.magic-links:*');
    $this->info('All magic links have been flushed.');
}
```

### Notes

- Always use `$this->confirm()` before flush/reset/delete operations.
- The confirmation message must describe what will be deleted and state that the action is irreversible.
- Commands should offer a `--force` flag to skip confirmation for scripted use:

```php
protected $signature = 'tyro-login:magic-links {--flush} {--force}';
```

---

## User Lookup Accepts ID or Email

### Why It Matters

Operators in different environments prefer different user identifiers. In development, numeric IDs are used frequently (via `tinker`, `dd()`, error messages). In production, support staff know user email addresses, not database IDs. Accepting both without requiring the operator to distinguish saves time and reduces errors.

### Incorrect

```php
// Only accepts ID — operator must convert email to ID manually
protected $signature = 'tyro-login:verify-user {id}';

public function handle(): void
{
    $userModel = config('tyro-login.user_model');
    $user = $userModel::findOrFail($this->argument('id'));
    $user->markEmailAsVerified();
    $this->info('User has been verified.');
}
```

### Correct

```php
// Accepts ID or email — operator provides whatever is available
protected $signature = 'tyro-login:verify-user {identifier : User ID or email}';

public function handle(): void
{
    $userModel = config('tyro-login.user_model');
    $identifier = $this->argument('identifier');

    $user = is_numeric($identifier)
        ? $userModel::findOrFail((int) $identifier)
        : $userModel::where('email', $identifier)->firstOrFail();

    $user->markEmailAsVerified();
    $this->info('✓ User has been verified.');
}
```

### Notes

- Use `is_numeric()` to determine whether the input is an ID or an email.
- Throw descriptive errors when the user is not found — `$this->error("User not found: {$identifier}")`.
- For bulk operations, provide a `--all` flag or accept a list.

---

## Standardized Success and Error Output

### Why It Matters

Inconsistent output formatting across commands makes it harder for operators to scan command output, especially when scripting. Standard prefixes for success, error, and info messages create a visual hierarchy that speeds up human reading.

### Incorrect

```php
// Inconsistent formatting — no visual hierarchy
$this->info('User verified');
$this->error('User not found');
$this->line('Operation completed');
```

### Correct

```php
// Standardized prefixes — visual hierarchy at a glance
$this->info('✓ User has been verified.');
// or
$this->components->success('User has been verified.');

$this->error('✗ User not found: ' . $identifier);
// or
$this->components->error('User not found: ' . $identifier);

$this->line('Operation completed.');
```

### Notes

- Success messages use `$this->info()` or `$this->components->success()` with a ✓ prefix.
- Error messages use `$this->error()` with a ✗ prefix.
- Use `$this->warn()` for warnings that are not errors.
- Use `$this->components->twoColumnDetail()` for listing operations when available.

---

## Gate Commands Behind Feature Config Checks

### Why It Matters

Running a command for a disabled feature (e.g., running `tyro-login:magic-links` when magic links are disabled) should produce a clear message, not an error or — worse — silently exit. The command's `handle()` method must check the corresponding config toggle first.

### Incorrect

```php
// No feature check — command runs even when feature is disabled
public function handle(): void
{
    $links = Cache::get('tyro-login.magic-links', []);
    // ... processes empty data or errors
}
```

### Correct

```php
// Feature check first — clear message when feature is disabled
public function handle(): void
{
    if (! config('tyro-login.features.magic_links_enabled')) {
        $this->warn('Magic links are not enabled. Enable them in config/tyro-login.php');
        return;
    }

    $links = Cache::get('tyro-login.magic-links', []);
    // ...
}
```

### Notes

- Check the feature toggle early in `handle()`, before any processing.
- Provide a clear message explaining how to enable the feature.
- This applies to: `MagicLinkCommand`, `InviteLinkCommand`, `ResetTwoFactorCommand`, `VerifyUserCommand`, `UnverifyUserCommand`.
