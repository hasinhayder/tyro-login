# Social Login

**Tier:** 1 — Structural
**Applies to:** `src/Http/Controllers/SocialAuthController.php`
**Cross-references:** [security.md](security.md) (session regeneration, encrypted token storage), [models-and-casts.md](models-and-casts.md) (SocialAccount model, EncryptedOrPlaintext cast), [suspension.md](suspension.md) (suspension check), [integration-boundaries.md](integration-boundaries.md) (soft-check Socialite, driver mapping)

Rules for OAuth provider redirects, callback handling, account linking, and social user creation.

---

## Use Session for OAuth Action State, Not URL Parameters

### Why It Matters

When redirecting to an OAuth provider, the controller needs to remember what action the user intended (login vs. linking an additional account). Storing this in URL state leaks intent in logs and referrer headers. Session state is invisible to the outside world.

### Incorrect

```php
// Action in URL — leaks in referrer header, visible in browser history
public function redirect(string $provider): RedirectResponse
{
    $action = request()->query('action', 'login');
    return Socialite::driver($provider)
        ->redirect();
    // Redirect URL contains no context — callback doesn't know the intent
}
```

### Correct

```php
// Action in session — invisible to external observers
public function redirect(string $provider): RedirectResponse
{
    session()->put('tyro-login.social.action', request()->query('action', 'login'));

    $driver = static::PROVIDER_DRIVER_MAP[$provider] ?? $provider;
    return Socialite::driver($driver)->redirect();
}
```

### Notes

- Session key: `tyro-login.social.action` with values like `login`, `register`, `link`.
- Clear the session key after reading it in the callback handler.
- This prevents action leakage via referrer headers and browser history.

---

## Handle Social Callback with a Clear State Machine

### Why It Matters

A social login callback can result in one of several outcomes: existing user links and logs in, new user is auto-registered, existing user has a new provider linked, or the provider email conflicts with an existing user. The callback method must handle all these cases in a clear, ordered state machine.

### Incorrect

```php
// Mixed concerns — account creation, linking, and login interleaved
public function callback(string $provider): RedirectResponse|View
{
    $driver = static::PROVIDER_DRIVER_MAP[$provider] ?? $provider;
    $providerUser = Socialite::driver($driver)->user();
    $user = User::where('email', $providerUser->getEmail())->first();

    if (! $user) {
        $user = User::create([
            'name' => $providerUser->getName(),
            'email' => $providerUser->getEmail(),
        ]);
    }

    SocialAccount::updateOrCreate([...]);
    Auth::login($user);
    return redirect()->intended(route('tyro-login.dashboard'));
}
```

### Correct

```php
// Ordered state machine — each case is explicit and isolated
public function callback(Request $request, string $provider): RedirectResponse
{
    // ... get social user ...

    $action = $request->session()->pull('tyro-login.social.action', 'login');

    return $this->handleSocialUser($request, $socialUser, $provider, $action);
}

protected function handleSocialUser(Request $request, SocialiteUser $socialUser, string $provider, string $action): RedirectResponse
{
    // Case 1: User already has this social account linked
    $socialAccount = SocialAccount::findByProvider($provider, $socialUser->getId());
    if ($socialAccount) {
        $this->updateSocialAccount($socialAccount, $socialUser);
        $this->markEmailAsVerified($socialAccount->user);
        Auth::login($socialAccount->user);
        $request->session()->regenerate();
        return $this->handlePostLoginRedirect($request, $socialAccount->user);
    }

    // Case 2: Existing user with this email — link social account
    $userModel = config('tyro-login.user_model');
    $user = $userModel::where('email', $socialUser->getEmail())->first();
    if ($user) {
        if (config('tyro-login.social.link_existing_accounts', true)) {
            $this->createSocialAccount($user, $socialUser, $provider);
            $this->markEmailAsVerified($user);
            Auth::login($user);
            $request->session()->regenerate();
            return $this->handlePostLoginRedirect($request, $user);
        }
        // Linking disabled — tell user to log in with password
        return redirect()->route('tyro-login.login')
            ->withErrors(['social' => 'An account with this email already exists.']);
    }

    // Case 3: No existing user — check if auto-registration is allowed
    if (! $this->shouldCreateMissingUser($action)) {
        return redirect()->route('tyro-login.login')
            ->withErrors(['social' => 'No account found.']);
    }

    if (! config('tyro-login.registration.enabled', true)) {
        return redirect()->route('tyro-login.login')
            ->withErrors(['social' => 'Registration is currently disabled.']);
    }

    $user = $this->createUser($socialUser);
    $this->createSocialAccount($user, $socialUser, $provider);
    $this->assignTyroRole($user);

    Auth::login($user);
    $request->session()->regenerate();
    return $this->handlePostLoginRedirect($request, $user, config('tyro-login.redirects.after_register'));
}
```

### Notes

- Each case is checked in order: existing social account → existing user (link) → new user (register).
- The action session key determines whether the flow is login or account linking.
- Registration must still respect the `tyro-login.registration.enabled` config toggle.
- Use `$request->session()->pull()` to read and clear the action in one call.

---

## Encrypt OAuth Tokens in the SocialAccount Model

### Why It Matters

OAuth access tokens and refresh tokens grant access to the user's third-party account (Google, Facebook, GitHub). A database breach that exposes these tokens allows an attacker to act as the user on the third-party service. These tokens must be encrypted at rest.

### Incorrect

```php
// Plaintext token storage — database breach exposes OAuth tokens
$socialAccount->access_token = $providerUser->token;
$socialAccount->refresh_token = $providerUser->refreshToken;
$socialAccount->save();
```

### Correct

```php
// Encrypted via EncryptedOrPlaintext cast — configured in model
class SocialAccount extends Model
{
    protected $casts = [
        'access_token' => EncryptedOrPlaintext::class,
        'refresh_token' => EncryptedOrPlaintext::class,
    ];
}

// The cast encrypts automatically on write
$socialAccount->access_token = $providerUser->token; // Encrypted on save
$socialAccount->refresh_token = $providerUser->refreshToken; // Encrypted on save
$socialAccount->save();
```

### Notes

- Both `access_token` and `refresh_token` must use `EncryptedOrPlaintext`.
- The cast includes legacy plaintext fallback for consumers upgrading from a pre-encryption version.
- Never log or dump OAuth tokens.

---

## Map OAuth Provider Names to Socialite Driver Names

### Why It Matters

Socialite driver names do not always match the common provider name. "LinkedIn" is `linkedin-openid`, not `linkedin`. "Slack" is `slack-openid`, not `slack`. A static mapping ensures that the user-facing provider name maps to the correct Socialite driver.

### Incorrect

```php
// Assumes provider name matches Socialite driver — breaks for LinkedIn, Slack
public function redirect(string $provider): RedirectResponse
{
    return Socialite::driver($provider)->redirect();
}
```

### Correct

```php
// Static driver mapping — handles name mismatches
protected const PROVIDER_DRIVER_MAP = [
    'google' => 'google',
    'facebook' => 'facebook',
    'github' => 'github',
    'twitter' => 'twitter',
    'linkedin' => 'linkedin-openid',
    'bitbucket' => 'bitbucket',
    'gitlab' => 'gitlab',
    'slack' => 'slack-openid',
];

protected function getDriver(string $provider): string
{
    return static::PROVIDER_DRIVER_MAP[$provider] ?? $provider;
}
```

### Notes

- The mapping is a constant on the controller, not config — provider-to-driver mappings are stable and defined by the Socialite package.
- Add a new entry for each supported provider.
- Use the existing `getEnabledProviders()` pattern to expose supported providers to the view.

---

## Handle Email Conflicts Gracefully

### Why It Matters

A user may already have an account with email X (registered via password) and then try to log in with a social provider that also has email X. The package must handle this by linking the social account to the existing user (if linking is enabled), not by creating a duplicate user or showing an error.

### Incorrect

```php
// Email conflict — throws error or creates duplicate
$existingUser = User::where('email', $providerUser->getEmail())->first();
if ($existingUser) {
    throw new \Exception('An account with this email already exists.');
}
```

### Correct

```php
// Email conflict — link provider to existing user (configurable)
$user = (config('tyro-login.user_model'))::where('email', $providerUser->getEmail())->first();

if ($user) {
    if (config('tyro-login.social.link_existing_accounts', true)) {
        $this->createSocialAccount($user, $socialUser, $provider);
        $this->markEmailAsVerified($user);
        Auth::login($user);
        $request->session()->regenerate();
        return $this->handlePostLoginRedirect($request, $user);
    }

    return redirect()->route('tyro-login.login')
        ->withErrors(['social' => 'An account with this email already exists. Please login with your password.']);
}
```

### Notes

- Linking is config-driven via `tyro-login.social.link_existing_accounts`, defaults to `true`.
- When linking is disabled, show a clear message directing the user to password login.
- Social login confirms email ownership — call `markEmailAsVerified()` when linking.
- Check suspension is handled by the `handlePostLoginRedirect()` method.
