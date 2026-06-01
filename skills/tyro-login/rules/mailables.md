# Mailables

**Tier:** 2 — Implementation
**Applies to:** `src/Mail/OtpMail.php`, `src/Mail/PasswordResetMail.php`, `src/Mail/VerifyEmailMail.php`, `src/Mail/WelcomeMail.php`, `src/Mail/MagicLinkMail.php`, all email Blade templates under `resources/views/emails/`
**Cross-references:** [config-and-env.md](config-and-env.md) (email config toggles and subjects), [controllers.md](controllers.md) (controller email sending), [security.md](security.md) (masked data in emails)

Rules for mailable classes, email template structure, queueing, and config-driven email management.

---

## Each Mailable Is Standalone

### Why It Matters

Every email type in Tyro Login serves a different purpose (OTP delivery, password reset, email verification, welcome, magic link). Combining them into a single mailable with parameters to switch behavior makes the class harder to test, harder to extend, and harder to override individually. One class per email type ensures single responsibility.

### Incorrect

```php
// Single mailable for all email types — violates single responsibility
class AuthMail extends Mailable
{
    public function __construct(
        public string $type, // 'otp', 'reset', 'verify', 'welcome', 'magic_link'
        public array $data,
    ) {}

    public function build(): self
    {
        return match ($this->type) {
            'otp' => $this->subject('OTP')->view('emails.otp'),
            'reset' => $this->subject('Reset Password')->view('emails.reset'),
            // ...
        };
    }
}
```

### Correct

```php
// Single-purpose mailable — easy to test, override, and extend
class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public string $userName,
        public int $expiresInMinutes,
    ) {}

    public function build(): self
    {
        return $this->subject(
            config('tyro-login.emails.otp.subject', 'Your One-Time Password')
        )->view('tyro-login::emails.otp');
    }
}
```

### Notes

- 5 mailables: `OtpMail`, `PasswordResetMail`, `VerifyEmailMail`, `WelcomeMail`, `MagicLinkMail`.
- Each mailable follows the same constructor pattern: specific, type-hinted parameters (not a generic `$data` array).
- The `build()` method reads the subject from config, never hardcodes it.

---

## Constructor Injection for Required Data

### Why It Matters

Mailables built with a generic `$data` array hide their dependencies. Developers have to read the template to discover what variables are available. Type-hinted constructor parameters make the contract explicit — the developer knows exactly what data to pass.

### Incorrect

```php
// Generic data array — template variables are invisible
class VerifyEmailMail extends Mailable
{
    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
```

### Correct

```php
// Explicit constructor parameters — self-documenting contract
class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $verificationUrl,
        public string $userName,
        public int $expiresInMinutes,
    ) {}
}
```

### Notes

- Each constructor parameter should be a `public` property — Laravel automatically makes them available to the Blade template.
- Parameters should be primitive types (`string`, `int`, `bool`) — never pass Eloquent models directly unless the mailable needs to serialize them.
- Use `SerializesModels` trait only when the constructor accepts an Eloquent model.

---

## Subject from Config with Fallback

### Why It Matters

Email subjects are part of the consumer's brand experience. A framework package must not force its own subject lines. Configurable subjects with polite defaults let consumers customize the email experience without overriding the mailable class.

### Incorrect

```php
// Hardcoded subject — consumer must override the mailable to change it
public function build(): self
{
    return $this->subject('Verify Your Email Address')
        ->view('tyro-login::emails.verify-email');
}
```

### Correct

```php
// Config-driven subject with fallback — consumer changes it in config
public function build(): self
{
    $subject = config('tyro-login.emails.verify_email.subject', 'Verify Your Email Address');
    return $this->subject($subject)
        ->view('tyro-login::emails.verify-email');
}
```

### Notes

- Config key pattern: `tyro-login.emails.{type}.subject`.
- `{type}` is the email type key: `otp`, `password_reset`, `verify_email`, `welcome`, `magic_link`.
- The config file documents each subject key with its default value.

---

## Template Data via `with()` for Explicit Variables

### Why It Matters

Public properties on the mailable are automatically available to the template. However, passing additional computed data or derived values via `with()` makes the template contract explicit. A mismatch between what the controller sends and what the template expects becomes visible in the mailable's `build()` method.

### Incorrect

```php
// Implicit data — template accesses any public property on the mailable
public function build(): self
{
    return $this->view('tyro-login::emails.welcome');
}
// Template relies on $loginUrl, $userName being set — hard to verify
```

### Correct

```php
// Explicit data via with() — template contract is visible in build()
public function build(): self
{
    return $this->subject(
        config('tyro-login.emails.welcome.subject', 'Welcome!')
    )->view('tyro-login::emails.welcome')
    ->with([
        'loginUrl' => $this->loginUrl,
        'userName' => $this->userName,
        'currentYear' => now()->year,
    ]);
}
```

### Notes

- Public properties are automatically available. Use `with()` for derived data or computed values.
- Document every template variable in the mailable — either as a property or in the `with()` array.

---

## All Emails Individually Toggleable

### Why It Matters

Consumers may want to suppress specific email types without overriding the controller logic. A global email toggle is not enough — each email type must have its own enable/disable switch. The controller must check the toggle before attempting to send.

### Incorrect

```php
// No toggle — email is always sent when the controller action runs
public function sendOtp(Request $request): void
{
    Mail::to($user)->send(new OtpMail($otp, $user->name, $expire));
}
```

### Correct

```php
// Config-driven toggle — email is only sent when enabled
protected function sendOtpEmail(User $user, string $otp, int $expire): void
{
    if (! config('tyro-login.emails.otp.enabled', true)) {
        return;
    }

    Mail::to($user)->send(new OtpMail($otp, $user->name, $expire));
}
```

### Notes

- Config key pattern: `tyro-login.emails.{type}.enabled`.
- Controllers call a `send*Email()` helper that checks the toggle before dispatching.
- The toggle defaults to `true` for existing email types, but new email types should default to `false`.
