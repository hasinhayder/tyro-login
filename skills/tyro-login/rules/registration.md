# Registration

**Tier:** 2 — Implementation
**Applies to:** `src/Http/Controllers/RegisterController.php`, `config/tyro-login.php` (registration, redirects sections)
**Cross-references:** [controllers.md](controllers.md) (validation, config checks), [password-policy.md](password-policy.md) (password validation rules), [captcha.md](captcha.md) (per-form captcha), [integration-boundaries.md](integration-boundaries.md) (Tyro role assignment), [mailables.md](mailables.md) (welcome email), [verification.md](verification.md) (email verification flow), [invitation.md](invitation.md) (referral tracking)

Rules for the registration flow — feature gating, user creation, post-registration flows (verification, auto-login, 2FA setup).

---

## Feature-Gate Registration via Config

### Why It Matters

Not all applications allow public registration. Some are invite-only, admin-created-only, or have registration temporarily disabled. The registration feature must be toggleable via config so that consumers can disable it without removing routes or overriding controllers.

### Incorrect

```php
// Registration always available — consumer cannot disable
public function showRegistrationForm(): View
{
    return view('tyro-login::register');
}
```

### Correct

```php
// Config-gated — redirects to login when disabled
public function showRegistrationForm(): View|RedirectResponse
{
    if (! config('tyro-login.registration.enabled', true)) {
        return redirect()->route('tyro-login.login');
    }
    // ... show form
}

public function register(Request $request): RedirectResponse
{
    if (! config('tyro-login.registration.enabled', true)) {
        abort(403, 'Registration is disabled.');
    }
    // ... process registration
}
```

### Notes

- Check the config toggle in BOTH the form display and the submission methods.
- The form redirects to login; the submission aborts with 403 — prevents bypass via direct POST.
- Config key: `tyro-login.registration.enabled`, defaults to `true`.

---

## Use Configured User Model for User Creation

### Why It Matters

The registration controller creates a new user. Using a hardcoded `User` model class breaks for consumers with custom user models or different namespaces. The config-driven model ensures the correct class is instantiated.

### Incorrect

```php
// Hardcoded User model — breaks with custom user models
$user = User::create([
    'name' => $validated['name'],
    'email' => $validated['email'],
    'password' => Hash::make($validated['password']),
]);
```

### Correct

```php
// Config-driven model — works with any consumer setup
$userModel = config('tyro-login.user_model', 'App\\Models\\User');

$user = $userModel::create([
    'name' => $validated['name'],
    'email' => $validated['email'],
    'password' => Hash::make($validated['password']),
]);
```

### Notes

- Default to `'App\\Models\\User'` if config is not set.
- This pattern applies to every location where a user is created or queried.

---

## Post-Registration Flow: Email Verification

### Why It Matters

When email verification is required, the newly registered user must NOT be auto-logged in. Instead, the verification email is sent and the user is redirected to the verification notice page. The user's email is stored in the session so the notice page can display it.

### Incorrect

```php
// Auto-login regardless of verification requirement — skips verification
$user = $userModel::create([...]);
Auth::login($user);
return redirect(config('tyro-login.redirects.after_register', '/'));
```

### Correct

```php
// Branch: verification required vs auto-login
if (config('tyro-login.registration.require_email_verification', false)) {
    VerificationController::generateVerificationUrl($user);
    $request->session()->put('tyro-login.verification.email', $user->email);
    return redirect()->route('tyro-login.verification.notice');
}

// Send welcome email only when verification is NOT required
if (config('tyro-login.emails.welcome.enabled', true)) {
    Mail::to($user->email)->send(new WelcomeMail(...));
}

if (config('tyro-login.registration.auto_login', true)) {
    // ... handle auto-login
}
```

### Notes

- Email verification and auto-login are mutually exclusive in the same request.
- The welcome email is sent only when verification is NOT required — otherwise the verification email serves as the first communication.
- Store the user's email in session under `tyro-login.verification.email` for the notice page.

---

## Post-Registration Flow: Auto-Login with 2FA Check

### Why It Matters

When auto-login is enabled and email verification is not required, the newly registered user should be logged in automatically. However, if 2FA is enabled globally, the user must be redirected to 2FA setup instead of being fully authenticated. The session stores the partial auth state for the 2FA setup flow.

### Incorrect

```php
// Auto-login without checking 2FA — bypasses 2FA setup for new users
Auth::login($user);
return redirect(config('tyro-login.redirects.after_register', '/'));
```

### Correct

```php
// Auto-login with 2FA awareness
if (config('tyro-login.registration.auto_login', true)) {
    if (config('tyro-login.two_factor.enabled', false)) {
        $request->session()->put('login.id', $user->id);
        $request->session()->put('login.remember', true);
        return redirect()->route('tyro-login.two-factor.setup');
    }

    Auth::login($user);
}

return redirect(config('tyro-login.redirects.after_register', '/'));
```

### Notes

- Session keys for 2FA setup: `login.id` and `login.remember`.
- When 2FA is enabled, the user is NOT logged in — the session only stores the user ID for the setup flow.
- `login.remember` defaults to `true` for the registration flow (the user just registered, so remember is expected).

---

## Tyro Role Assignment as Optional Integration

### Why It Matters

Role assignment via the Tyro package is an optional integration. If Tyro is not installed, the registration must succeed without it. The role assignment method uses soft checks and graceful failure handling.

### Incorrect

```php
// Hard Tyro dependency — registration fails if Tyro is not installed
use HasinHayder\Tyro\Models\Role;

$role = Role::where('slug', 'user')->first();
$user->assignRole($role);
```

### Correct

```php
// Soft integration — gracefully skips when Tyro is not available
protected function assignTyroRole($user): void
{
    if (! config('tyro-login.tyro.assign_default_role', true)) {
        return;
    }

    if (! class_exists('HasinHayder\\Tyro\\Models\\Role')) {
        return;
    }

    if (! method_exists($user, 'assignRole')) {
        return;
    }

    try {
        $roleModel = 'HasinHayder\\Tyro\\Models\\Role';
        $role = $roleModel::where('slug', config('tyro-login.tyro.default_role_slug', 'user'))->first();
        if ($role) {
            $user->assignRole($role);
        }
    } catch (\Exception $e) {
        report($e);
    }
}
```

### Notes

- Triple check: config toggle, class existence, method existence.
- Wrap in try/catch — database errors (missing table) should not break registration.
- Use `report($e)` to report without rethrowing.
- This method is duplicated in `RegisterController` and `SocialAuthController` — consider extracting to a trait in a future version.

---

## Config-Driven Redirects After Registration

### Why It Matters

After registration, the redirect destination varies by application. Some redirect to a dashboard, others to a welcome page, others to the intended URL. The redirect must be config-driven.

### Incorrect

```php
// Hardcoded redirect — consumer cannot customize
return redirect('/dashboard');
```

### Correct

```php
// Config-driven redirect
return redirect(config('tyro-login.redirects.after_register', '/'));
```

### Notes

- Config key: `tyro-login.redirects.after_register`, defaults to `'/'`.
- When email verification is required, the redirect goes to the verification notice instead.
- When 2FA setup is required, the redirect goes to the 2FA setup page instead.
