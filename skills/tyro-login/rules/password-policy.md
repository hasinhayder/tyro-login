# Password Policy

**Tier:** 2 — Implementation
**Applies to:** `src/Http/Controllers/RegisterController.php` (password validation), `src/Http/Controllers/PasswordResetController.php` (reset validation), `config/tyro-login.php` (password section)
**Cross-references:** [config-and-env.md](config-and-env.md) (password.* config keys), [controllers.md](controllers.md) (validation rules extraction), [security.md](security.md) (validation before operations)

Rules for password complexity requirements, common password checking, user-information disallowal, and confirmation enforcement.

---

## Config-Driven Password Complexity via Laravel's Password Rule

### Why It Matters

Password complexity requirements vary widely across organizations. Some require mixed case and special characters; others only require a minimum length. Hardcoding complexity rules forces consumers to override the controller to change policy. Using Laravel's `Illuminate\Validation\Rules\Password` with config-driven parameters allows customization without code changes.

### Incorrect

```php
// Hardcoded complexity — consumer cannot customize
$rules = [
    'password' => ['required', 'string', 'min:8', 'confirmed',
        'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^a-zA-Z0-9]/'],
];
```

### Correct

```php
// Config-driven complexity — consumer changes config
protected function getValidationRules(): array
{
    $minLength = config('tyro-login.password.min_length', 8);
    $maxLength = config('tyro-login.password.max_length');

    $passwordRule = Password::min($minLength);

    if ($maxLength) {
        $passwordRule = $passwordRule->max($maxLength);
    }

    $complexity = config('tyro-login.password.complexity', []);

    if (! empty($complexity['require_uppercase']) || ! empty($complexity['require_lowercase'])) {
        $passwordRule = $passwordRule->mixedCase();
    }

    if ($complexity['require_numbers'] ?? false) {
        $passwordRule = $passwordRule->numbers();
    }

    if ($complexity['require_special_chars'] ?? false) {
        $passwordRule = $passwordRule->symbols();
    }

    $passwordRules = ['required', 'string', $passwordRule];

    return ['password' => $passwordRules, ...];
}
```

### Notes

- Config keys: `tyro-login.password.min_length`, `tyro-login.password.max_length`, `tyro-login.password.complexity.*`.
- When both `require_uppercase` and `require_lowercase` are specified, use `mixedCase()` which covers both.
- The `Password` rule object is composable — chain methods to build the exact policy.

---

## Dynamic Table Name for Unique Email Validation

### Why It Matters

The registration form validates that the email is unique in the users table. The users table name is not always `users` — the consumer may have customized it via their User model. Resolving the table name from the configured User model ensures the validation works with any table name.

### Incorrect

```php
// Hardcoded table name — breaks if consumer uses a different table
'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
```

### Correct

```php
// Dynamic table name — resolved from the configured User model
$userModel = config('tyro-login.user_model', 'App\\Models\\User');
$usersTable = (new $userModel)->getTable();

$rules = [
    'email' => ['required', 'string', 'email', 'max:255', 'unique:'.$usersTable],
];
```

### Notes

- Instantiate the model to call `getTable()` — this returns the Eloquent table name.
- This pattern applies wherever the users table is referenced in validation rules.

---

## Common Password Check via Config Toggle

### Why It Matters

A list of commonly-used passwords (password, 123456, qwerty, etc.) defeats the purpose of complexity rules if users can still choose them. A config toggle lets consumers opt into this additional check without modifying code.

### Incorrect

```php
// No common password check — user chooses "password123" that meets complexity rules
$passwordRule = Password::min(8)->mixedCase()->numbers();
```

### Correct

```php
// Config-driven common password check
if (config('tyro-login.password.check_common_passwords', false)) {
    $passwordRules[] = function ($attribute, $value, $fail) {
        $commonPasswords = [
            'password', '123456', '123456789', '12345678', '12345', '1234567',
            '1234567890', '1234', 'qwerty', 'abc123', 'password123', 'admin',
            'letmein', 'welcome', 'monkey', '1234567890', 'password1',
        ];

        if (in_array(strtolower($value), $commonPasswords)) {
            $fail('This password is too common. Please choose a more secure password.');
        }
    };
}
```

### Notes

- Config key: `tyro-login.password.check_common_passwords`, defaults to `false`.
- Compare `strtolower($value)` against the list — case-insensitive matching.
- The list should be maintained and expanded in future versions.

---

## Disallow Passwords Containing User Information

### Why It Matters

A password that contains the user's name or email username is weak even if it meets complexity requirements. An attacker who knows the user's name can try combinations. This check is config-driven and performed as a separate validation step after the standard rules pass.

### Incorrect

```php
// No user info check — user sets password as "John2024!" which meets complexity
$passwordRule = Password::min(8)->mixedCase()->numbers()->symbols();
```

### Correct

```php
// Separate validation step after standard rules
protected function validatePasswordNotContainingUserInfo(Request $request, array $validated): void
{
    if (! config('tyro-login.password.disallow_user_info', false)) {
        return;
    }

    $password = strtolower($validated['password']);
    $name = strtolower($validated['name']);
    $email = strtolower($validated['email']);
    $emailUsername = explode('@', $email)[0];
    $nameParts = preg_split('/[\s\-_]+/', $name);

    $errors = [];

    if (strlen($emailUsername) >= 3 && str_contains($password, $emailUsername)) {
        $errors[] = 'password cannot contain your email username';
    }

    foreach ($nameParts as $namePart) {
        if (strlen($namePart) >= 3 && str_contains($password, $namePart)) {
            $errors[] = 'password cannot contain parts of your name';
            break;
        }
    }

    if (! empty($errors)) {
        throw ValidationException::withMessages([
            'password' => 'For security reasons, your '.implode(' and ', $errors).'.',
        ]);
    }
}
```

### Notes

- Config key: `tyro-login.password.disallow_user_info`, defaults to `false`.
- Only check name parts and email username with length >= 3 to avoid false positives on short strings.
- Split name by spaces, hyphens, and underscores to catch all name parts.
- Throw a `ValidationException` with a combined message listing all violations.

---

## Password Confirmation Is Configurable

### Why It Matters

Some consumers prefer a single password field (especially on mobile or when the registration form is already short). Making the `confirmed` rule configurable lets consumers choose their UX without overriding the entire validation method.

### Incorrect

```php
// Always requires confirmation — consumer cannot remove it
$rules['password'][] = 'confirmed';
```

### Correct

```php
// Config-driven confirmation
if (config('tyro-login.password.require_confirmation', true)) {
    $rules['password'][] = 'confirmed';
}
```

### Notes

- Config key: `tyro-login.password.require_confirmation`, defaults to `true`.
- When enabled, the form must include a `password_confirmation` field.
- This applies to both registration and password reset forms.

---

## Password Hashing via Laravel's Hash Facade

### Why It Matters

Laravel's `Hash` facade uses the application's configured hashing driver (bcrypt by default, argon2id as an alternative). Never use PHP's `password_hash()` directly — the `Hash` facade ensures consistency with Laravel's hashing configuration and allows consumers to switch algorithms.

### Incorrect

```php
// Direct PHP function — ignores Laravel's hashing config
$user->password = password_hash($validated['password'], PASSWORD_BCRYPT);
```

### Correct

```php
// Laravel Hash facade — respects configured driver
$user->password = Hash::make($validated['password']);
```

### Notes

- Always use `Hash::make()` — it uses the driver configured in `config/hashing.php`.
- Never compare passwords manually — always use `Hash::check()` (used internally by `Auth::attempt()`).
