# Captcha

**Tier:** 2 — Implementation
**Applies to:** `src/Http/Controllers/LoginController.php` (captcha methods), `src/Http/Controllers/RegisterController.php` (captcha methods)
**Cross-references:** [security.md](security.md) (random number generation), [controllers.md](controllers.md) (protected helper methods, session for state), [config-and-env.md](config-and-env.md) (captcha.* config keys)

Rules for math-based captcha generation, session storage, per-form configuration, and validation.

---

## Math-Based Captcha for Bot Deterrence

### Why It Matters

Automated bots can submit login and registration forms at scale. A math-based captcha adds a lightweight challenge that blocks most automated submissions without requiring external services (reCAPTCHA, hCaptcha) or JavaScript execution. The captcha is config-driven and per-form — consumers enable it only where needed.

### Incorrect

```php
// No captcha — bot submits login forms at scale
public function login(Request $request): RedirectResponse
{
    $credentials = $request->validate([
        'email' => ['required', 'string', 'email'],
        'password' => ['required', 'string'],
    ]);
    // ... authenticate
}
```

### Correct

```php
// Captcha validated before authentication proceeds
public function login(Request $request): RedirectResponse
{
    $rules = $this->getValidationRules($loginField);

    if (config('tyro-login.captcha.enabled_login', false)) {
        $rules['captcha_answer'] = ['required', 'numeric'];
    }

    $credentials = $request->validate($rules);

    if (config('tyro-login.captcha.enabled_login', false)) {
        if (! $this->validateCaptcha($request, 'login', $credentials['captcha_answer'])) {
            $this->generateCaptcha($request, 'login');
            throw ValidationException::withMessages([
                'captcha_answer' => config('tyro-login.captcha.error_message', 'Incorrect answer. Please try again.'),
            ]);
        }
        unset($credentials['captcha_answer']);
    }
    // ... authenticate
}
```

### Notes

- Captcha is independently toggleable per form: `captcha.enabled_login`, `captcha.enabled_register`.
- Validate captcha before authentication logic, then remove `captcha_answer` from credentials.
- Regenerate the captcha after a failed attempt to prevent replay.

---

## Generate Captcha with `random_int()`

### Why It Matters

The captcha numbers must use a CSPRNG to prevent attackers from predicting the generated values. While captcha is not a primary security mechanism (it's bot deterrence), using `random_int()` maintains consistency with the package's security standard for all random generation.

### Incorrect

```php
// Predictable numbers — attacker can guess captcha answers
$num1 = rand($min, $max);
$num2 = rand($min, $max);
$isAddition = (bool) rand(0, 1);
```

### Correct

```php
// Cryptographically secure — each number is independently random
protected function generateCaptcha(Request $request, string $context): array
{
    $min = config('tyro-login.captcha.min_number', 1);
    $max = config('tyro-login.captcha.max_number', 10);

    $num1 = random_int($min, $max);
    $num2 = random_int($min, $max);
    $isAddition = (bool) random_int(0, 1);

    if ($isAddition) {
        $question = "$num1 + $num2 = ?";
        $answer = $num1 + $num2;
    } else {
        if ($num1 < $num2) {
            [$num1, $num2] = [$num2, $num1];
        }
        $question = "$num1 - $num2 = ?";
        $answer = $num1 - $num2;
    }

    $request->session()->put("tyro-login.captcha.{$context}", $answer);

    return ['question' => $question, 'answer' => $answer];
}
```

### Notes

- Always use `random_int()`, never `rand()` or `mt_rand()`.
- For subtraction, ensure the first number is larger to keep results positive.
- The answer is stored in the session, not in a hidden form field.

---

## Session-Based Captcha Answer Storage

### Why It Matters

The captcha answer must be stored server-side, not in a hidden form field or cookie that the client can read. Session storage is the correct mechanism because the captcha is tied to the user's current browser session and is automatically scoped.

### Incorrect

```php
// Answer in hidden field — client can read and bypass
<input type="hidden" name="captcha_answer_hash" value="{{ md5($answer) }}">
```

### Correct

```php
// Answer in session — server-side only, scoped to session
protected function generateCaptcha(Request $request, string $context): array
{
    // ... generate question and answer
    $request->session()->put("tyro-login.captcha.{$context}", $answer);
    return ['question' => $question, 'answer' => $answer];
}

protected function validateCaptcha(Request $request, string $context, $answer): bool
{
    $expected = $request->session()->get("tyro-login.captcha.{$context}");

    if ($expected === null) {
        return false;
    }

    $request->session()->forget("tyro-login.captcha.{$context}");

    return (int) $answer === (int) $expected;
}
```

### Notes

- Session key pattern: `tyro-login.captcha.{context}` where context is `login` or `register`.
- Clear the captcha from session immediately after validation — single use.
- Cast both values to `(int)` for comparison to handle string/form input.
- The `validateCaptcha()` method must clear the session value after reading to prevent reuse.

---

## Per-Form Captcha Configuration

### Why It Matters

Login and registration forms have different risk profiles. Login forms face credential stuffing, while registration forms face bulk account creation. Consumers need independent control over which forms require captcha without a global toggle.

### Incorrect

```php
// Global captcha toggle — either all forms or none
if (config('tyro-login.captcha.enabled', false)) {
    // Applies to both login and register — cannot enable for one without the other
}
```

### Correct

```php
// Per-form toggle — independent control
if (config('tyro-login.captcha.enabled_login', false)) {
    // Only login form
}

if (config('tyro-login.captcha.enabled_register', false)) {
    // Only registration form
}
```

### Notes

- Config keys: `tyro-login.captcha.enabled_login`, `tyro-login.captcha.enabled_register`.
- Both default to `false` — captcha is opt-in.
- Additional config: `captcha.min_number`, `captcha.max_number`, `captcha.error_message`.
- The view receives `captchaEnabled` and `captchaQuestion` to conditionally render the captcha UI.

---

## Regenerate Captcha After Failed Validation

### Why It Matters

If the captcha answer is not regenerated after a failed attempt, the attacker can reuse the same answer repeatedly. Each failed attempt must produce a new captcha to force the attacker to solve a new challenge each time.

### Incorrect

```php
// No regeneration — same captcha reused after failure
if (! $this->validateCaptcha($request, 'login', $credentials['captcha_answer'])) {
    throw ValidationException::withMessages([
        'captcha_answer' => 'Incorrect answer.',
    ]);
}
```

### Correct

```php
// Regenerate on failure — new challenge for each attempt
if (! $this->validateCaptcha($request, 'login', $credentials['captcha_answer'])) {
    $this->generateCaptcha($request, 'login');
    throw ValidationException::withMessages([
        'captcha_answer' => config('tyro-login.captcha.error_message', 'Incorrect answer. Please try again.'),
    ]);
}
```

### Notes

- Call `$this->generateCaptcha()` before throwing the validation exception.
- The next form render will show the new captcha question.
- Also regenerate on failed login attempts (after `Auth::attempt` fails) to rotate the challenge.
