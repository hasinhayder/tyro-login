<?php

namespace HasinHayder\TyroLogin\Http\Controllers;

use HasinHayder\TyroLogin\Mail\OtpMail;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller {
    /**
     * Show the login form.
     */
    public function showLoginForm(Request $request): View|RedirectResponse {
        // Check if user is locked out
        if ($this->isLockedOut($request)) {
            return redirect()->route('tyro-login.lockout');
        }

        // Generate captcha if enabled
        $captcha = $this->generateCaptcha($request, 'login');

        $loginField = config('tyro-login.login_field', 'email');

        return view('tyro-login::login', [
            'layout' => config('tyro-login.layout', 'centered'),
            'branding' => config('tyro-login.branding'),
            'backgroundImage' => config('tyro-login.background_image'),
            'features' => config('tyro-login.features'),
            'registrationEnabled' => config('tyro-login.registration.enabled', true),
            'pageContent' => config('tyro-login.pages.login'),
            'captchaEnabled' => config('tyro-login.captcha.enabled_login', false),
            'captchaQuestion' => $captcha['question'] ?? null,
            'captchaConfig' => config('tyro-login.captcha'),
            'loginField' => $loginField,
        ]);
    }

    /**
     * Show the lockout page.
     */
    public function showLockout(Request $request): View|RedirectResponse {
        // Check if user is still locked out
        if (!$this->isLockedOut($request)) {
            // Clear the lockout cache and redirect to login
            $this->clearLockout($request);
            return redirect()->route('tyro-login.login');
        }

        $releaseTime = $this->getLockoutReleaseTime($request);
        $remainingMinutes = $releaseTime ? max(1, (int) ceil(($releaseTime - now()->timestamp) / 60)) : 0;

        $message = str_replace(
            ':minutes',
            (string) $remainingMinutes,
            config('tyro-login.lockout.message', 'Too many failed login attempts. Please try again in :minutes minutes.')
        );

        return view('tyro-login::lockout', [
            'layout' => config('tyro-login.layout', 'centered'),
            'branding' => config('tyro-login.branding'),
            'backgroundImage' => config('tyro-login.background_image'),
            'title' => config('tyro-login.lockout.title', 'Account Temporarily Locked'),
            'subtitle' => config('tyro-login.lockout.subtitle', 'For your security, we\'ve temporarily locked your account.'),
            'message' => $message,
            'remainingMinutes' => $remainingMinutes,
            'releaseTime' => $releaseTime,
        ]);
    }

    /**
     * Handle a login request.
     */
    public function login(Request $request): RedirectResponse {
        // Check if user is locked out
        if ($this->isLockedOut($request)) {
            return redirect()->route('tyro-login.lockout');
        }

        $loginField = config('tyro-login.login_field', 'email');

        // Get validation rules (includes captcha if enabled)
        $rules = $this->getValidationRules($loginField);

        // Add captcha validation if enabled
        if (config('tyro-login.captcha.enabled_login', false)) {
            $rules['captcha_answer'] = ['required', 'numeric'];
        }


        $credentials = $request->validate($rules);

        // Handle 'both' login field - determine if input is email or username
        if ($loginField === 'both') {
            $loginValue = $credentials['login'];
            $field = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
            $credentials[$field] = $loginValue;
            unset($credentials['login']);
        }

        // Validate captcha if enabled
        if (config('tyro-login.captcha.enabled_login', false)) {
            if (!$this->validateCaptcha($request, 'login', $credentials['captcha_answer'])) {
                // Regenerate captcha for next attempt
                $this->generateCaptcha($request, 'login');

                throw ValidationException::withMessages([
                    'captcha_answer' => config('tyro-login.captcha.error_message', 'Incorrect answer. Please try again.'),
                ]);
            }
            unset($credentials['captcha_answer']);
        }

        $remember = config('tyro-login.features.remember_me', true)
            ? $request->boolean('remember')
            : false;

        // Remove remember from credentials if it exists
        unset($credentials['remember']);

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $this->clearLockout($request);

            $user = Auth::user();

            // Check if email verification is required and email is not verified
            if (config('tyro-login.registration.require_email_verification', false) && !$user->hasVerifiedEmail()) {
                // Log the user out - they need to verify first
                Auth::logout();

                // Store email in session for verification page
                $request->session()->put('tyro-login.verification.email', $user->email);

                // Redirect to email-not-verified page (don't resend verification email)
                return redirect()->route('tyro-login.verification.not-verified');
            }

            // Check if 2FA is enabled
            if (config('tyro-login.two_factor.enabled', false)) {
                if (filled($user->two_factor_confirmed_at)) {
                    // User has 2FA enabled - lock them out until verified
                    Auth::logout();
                    
                    $request->session()->put('login.id', $user->id);
                    $request->session()->put('login.remember', $remember);
                    
                    return redirect()->route('tyro-login.two-factor.challenge');
                } else {
                    // User hasn't set up 2FA yet - redirect to setup
                    // Log them out to ensure they can't bypass setup
                    Auth::logout();
                    
                    $request->session()->put('login.id', $user->id);
                    $request->session()->put('login.remember', $remember);

                    return redirect()->route('tyro-login.two-factor.setup');
                }
            }

            // Check if OTP is enabled
            if (config('tyro-login.otp.enabled', false)) {
                // Store user ID and remember preference before logout
                $userId = $user->id;
                $rememberPreference = $remember;

                // Log the user out and regenerate session to prevent session fixation
                Auth::logout();
                $request->session()->regenerate();

                // Store data in the new session
                $request->session()->put('tyro-login.otp.user_id', $userId);
                $request->session()->put('tyro-login.otp.remember', $rememberPreference);

                // Generate and send OTP
                $this->generateAndSendOtp($request, $user);

                return redirect()->route('tyro-login.otp.verify');
            }

            return redirect()->intended(config('tyro-login.redirects.after_login', '/'));
        }

        $this->incrementLockoutAttempts($request);

        // Regenerate captcha for next attempt
        if (config('tyro-login.captcha.enabled_login', false)) {
            $this->generateCaptcha($request, 'login');
        }

        // Check if we should lock out the user now
        if ($this->shouldLockout($request)) {
            $this->lockoutUser($request);
            return redirect()->route('tyro-login.lockout');
        }

        // Build error message with remaining attempts if configured
        $errorMessage = __('auth.failed');

        if (config('tyro-login.lockout.enabled', true) && config('tyro-login.lockout.show_attempts_left', false)) {
            $attemptsLeft = $this->getRemainingAttempts($request);
            if ($attemptsLeft > 0) {
                $errorMessage .= ' ' . trans_choice(
                    '{1} :count attempt remaining.|[2,*] :count attempts remaining.',
                    $attemptsLeft,
                    ['count' => $attemptsLeft]
                );
            }
        }

        // Use 'login' as error field when loginField is 'both', otherwise use the actual field name
        $errorField = $loginField === 'both' ? 'login' : $loginField;

        throw ValidationException::withMessages([
            $errorField => $errorMessage,
        ]);
    }

    /**
     * Show the OTP verification form.
     */
    public function showOtpForm(Request $request): View|RedirectResponse {
        // Check if we have a pending OTP verification
        if (!$request->session()->has('tyro-login.otp.user_id')) {
            return redirect()->route('tyro-login.login');
        }

        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        $userId = $request->session()->get('tyro-login.otp.user_id');
        $user = $userModel::find($userId);

        if (!$user) {
            $request->session()->forget('tyro-login.otp');
            return redirect()->route('tyro-login.login');
        }

        $otpConfig = config('tyro-login.otp');
        $resendCount = $request->session()->get('tyro-login.otp.resend_count', 0);
        $lastResendTime = $request->session()->get('tyro-login.otp.last_resend', 0);
        $cooldown = $otpConfig['resend_cooldown'] ?? 60;
        $canResend = (time() - $lastResendTime) >= $cooldown;
        $remainingCooldown = max(0, $cooldown - (time() - $lastResendTime));

        // Mask email
        $email = $user->email;
        $maskedEmail = $this->maskEmail($email);

        $subtitle = str_replace(
            [':length', ':email'],
            [$otpConfig['length'] ?? 4, $maskedEmail],
            $otpConfig['subtitle'] ?? 'We\'ve sent a :length-digit code to :email'
        );

        return view('tyro-login::otp-verify', [
            'layout' => config('tyro-login.layout', 'centered'),
            'branding' => config('tyro-login.branding'),
            'backgroundImage' => config('tyro-login.background_image'),
            'otpConfig' => $otpConfig,
            'title' => $otpConfig['title'] ?? 'Enter Verification Code',
            'subtitle' => $subtitle,
            'canResend' => $canResend && $resendCount < ($otpConfig['max_resend'] ?? 3),
            'remainingCooldown' => $remainingCooldown,
            'resendCount' => $resendCount,
            'maxResend' => $otpConfig['max_resend'] ?? 3,
            'otpLength' => $otpConfig['length'] ?? 4,
        ]);
    }

    /**
     * Verify the OTP.
     */
    public function verifyOtp(Request $request): RedirectResponse {
        // Check if we have a pending OTP verification
        if (!$request->session()->has('tyro-login.otp.user_id')) {
            return redirect()->route('tyro-login.login');
        }

        $request->validate([
            'otp' => ['required', 'string'],
        ]);

        $userId = $request->session()->get('tyro-login.otp.user_id');
        $remember = $request->session()->get('tyro-login.otp.remember', false);

        // Verify OTP
        $cacheKey = $this->getOtpCacheKey($userId);
        $storedOtp = Cache::get($cacheKey);

        if (!$storedOtp || $storedOtp !== $request->input('otp')) {
            throw ValidationException::withMessages([
                'otp' => config('tyro-login.otp.error_message', 'Invalid or expired verification code.'),
            ]);
        }

        // OTP is valid - log the user in
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        $user = $userModel::find($userId);

        if (!$user) {
            $request->session()->forget('tyro-login.otp');
            return redirect()->route('tyro-login.login');
        }

        // Clear OTP cache
        Cache::forget($cacheKey);
        Cache::forget($this->getOtpCacheKey($userId) . ':resend');

        // Clear session data
        $request->session()->forget('tyro-login.otp');

        // Log the user in
        Auth::login($user, $remember);

        return redirect()->intended(config('tyro-login.redirects.after_login', '/'));
    }

    /**
     * Resend the OTP.
     */
    public function resendOtp(Request $request): RedirectResponse {
        // Check if we have a pending OTP verification
        if (!$request->session()->has('tyro-login.otp.user_id')) {
            return redirect()->route('tyro-login.login');
        }

        $userId = $request->session()->get('tyro-login.otp.user_id');
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        $user = $userModel::find($userId);

        if (!$user) {
            $request->session()->forget('tyro-login.otp');
            return redirect()->route('tyro-login.login');
        }

        $otpConfig = config('tyro-login.otp');
        $resendCount = $request->session()->get('tyro-login.otp.resend_count', 0);
        $lastResendTime = $request->session()->get('tyro-login.otp.last_resend', 0);
        $cooldown = $otpConfig['resend_cooldown'] ?? 60;

        // Check if cooldown has passed
        if ((time() - $lastResendTime) < $cooldown) {
            return redirect()->route('tyro-login.otp.verify')
                ->withErrors(['otp' => 'Please wait before requesting a new code.']);
        }

        // Check max resend attempts
        if ($resendCount >= ($otpConfig['max_resend'] ?? 3)) {
            $request->session()->forget('tyro-login.otp');
            return redirect()->route('tyro-login.login')
                ->withErrors(['email' => $otpConfig['max_resend_error'] ?? 'Maximum resend attempts reached. Please try logging in again.']);
        }

        // Regenerate and send OTP
        $this->generateAndSendOtp($request, $user);

        // Update resend count and time
        $request->session()->put('tyro-login.otp.resend_count', $resendCount + 1);
        $request->session()->put('tyro-login.otp.last_resend', time());

        return redirect()->route('tyro-login.otp.verify')
            ->with('success', $otpConfig['resend_success'] ?? 'A new verification code has been sent to your email.');
    }

    /**
     * Cancel OTP verification and return to login.
     */
    public function cancelOtp(Request $request): RedirectResponse {
        // Clear OTP session data
        if ($request->session()->has('tyro-login.otp.user_id')) {
            $userId = $request->session()->get('tyro-login.otp.user_id');
            Cache::forget($this->getOtpCacheKey($userId));
        }

        $request->session()->forget('tyro-login.otp');

        return redirect()->route('tyro-login.login');
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): RedirectResponse {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(config('tyro-login.redirects.after_logout', '/login'));
    }

    /**
     * Get validation rules based on login field.
     */
    protected function getValidationRules(string $loginField): array {
        $rules = [
            'password' => ['required', 'string'],
        ];

        if ($loginField === 'email') {
            $rules['email'] = ['required', 'string', 'email'];
        } elseif ($loginField === 'username') {
            $rules['username'] = ['required', 'string'];
        } else {
            // 'both' - accept either email or username
            $rules['login'] = ['required', 'string'];
        }

        return $rules;
    }

    /**
     * Generate a math captcha.
     */
    protected function generateCaptcha(Request $request, string $context): array {
        $min = config('tyro-login.captcha.min_number', 1);
        $max = config('tyro-login.captcha.max_number', 10);

        $num1 = rand($min, $max);
        $num2 = rand($min, $max);

        // Randomly choose addition or subtraction
        $isAddition = (bool) rand(0, 1);

        if ($isAddition) {
            $question = "$num1 + $num2 = ?";
            $answer = $num1 + $num2;
        } else {
            // Ensure first number is larger for positive result
            if ($num1 < $num2) {
                [$num1, $num2] = [$num2, $num1];
            }
            $question = "$num1 - $num2 = ?";
            $answer = $num1 - $num2;
        }

        // Store answer in session
        $request->session()->put("tyro-login.captcha.{$context}", $answer);

        return [
            'question' => $question,
            'answer' => $answer,
        ];
    }

    /**
     * Validate the captcha answer.
     */
    protected function validateCaptcha(Request $request, string $context, $answer): bool {
        $expected = $request->session()->get("tyro-login.captcha.{$context}");

        if ($expected === null) {
            return false;
        }

        // Clear the captcha from session after validation
        $request->session()->forget("tyro-login.captcha.{$context}");

        return (int) $answer === (int) $expected;
    }

    /**
     * Generate and send OTP to user.
     */
    protected function generateAndSendOtp(Request $request, $user): void {
        $length = config('tyro-login.otp.length', 4);
        $expire = config('tyro-login.otp.expire', 5);

        // Generate cryptographically secure OTP
        $min = (int) (10 ** ($length - 1));
        $max = (int) ((10 ** $length) - 1);
        $otp = (string) random_int($min, $max);

        // Store OTP in cache
        $cacheKey = $this->getOtpCacheKey($user->id);
        Cache::put($cacheKey, $otp, now()->addMinutes($expire));

        // Log OTP for development (only if debug is enabled)
        if (config('tyro-login.debug', false)) {
            Log::info('Tyro Login - OTP Generated', [
                'user_id' => $user->id,
                'email' => Str::mask($user->email, '*', 3),
                'otp_length' => $length,
                'expires_in_minutes' => $expire,
            ]);
        }

        // Send OTP via email if enabled
        if (config('tyro-login.emails.otp.enabled', true)) {
            Mail::to($user->email)->send(new OtpMail(
                otp: $otp,
                userName: $user->name ?? 'User',
                expiresInMinutes: $expire
            ));
        }

        // Initialize resend tracking if not exists
        if (!$request->session()->has('tyro-login.otp.resend_count')) {
            $request->session()->put('tyro-login.otp.resend_count', 0);
            $request->session()->put('tyro-login.otp.last_resend', time());
        }
    }

    /**
     * Get the cache key for OTP.
     */
    protected function getOtpCacheKey($userId): string {
        return "tyro-login:otp:{$userId}";
    }

    /**
     * Mask email address for display.
     */
    protected function maskEmail(string $email): string {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $name = $parts[0];
        $domain = $parts[1];

        if (strlen($name) <= 2) {
            $maskedName = $name[0] . '***';
        } else {
            $maskedName = substr($name, 0, 2) . str_repeat('*', min(strlen($name) - 2, 5));
        }

        return $maskedName . '@' . $domain;
    }

    /**
     * Get the lockout cache key for the request.
     */
    protected function lockoutKey(Request $request): string {
        return 'tyro-login:lockout:' . $request->ip();
    }

    /**
     * Get the lockout attempts cache key for the request.
     */
    protected function lockoutAttemptsKey(Request $request): string {
        return 'tyro-login:lockout-attempts:' . $request->ip();
    }

    /**
     * Check if the user is currently locked out.
     */
    protected function isLockedOut(Request $request): bool {
        if (!config('tyro-login.lockout.enabled', true)) {
            return false;
        }

        $releaseTime = $this->getLockoutReleaseTime($request);

        if (!$releaseTime) {
            return false;
        }

        // If lockout has expired, clear it
        if (now()->timestamp >= $releaseTime) {
            $this->clearLockout($request);
            return false;
        }

        return true;
    }

    /**
     * Get the lockout release timestamp.
     */
    protected function getLockoutReleaseTime(Request $request): ?int {
        return Cache::get($this->lockoutKey($request));
    }

    /**
     * Increment the lockout attempt counter.
     */
    protected function incrementLockoutAttempts(Request $request): void {
        if (!config('tyro-login.lockout.enabled', true)) {
            return;
        }

        $key = $this->lockoutAttemptsKey($request);
        $attempts = Cache::get($key, 0);
        $maxAttempts = config('tyro-login.lockout.max_attempts', 5);

        // If attempts are already at max (or more) but the user wasn't caught by isLockedOut,
        // it means the lockout expired naturally. We should reset the counter.
        if ($attempts >= $maxAttempts) {
            $attempts = 0;
        }

        $attempts++;

        // Store attempts for the lockout duration + some buffer time
        $durationMinutes = (int) config('tyro-login.lockout.duration_minutes', 15);
        Cache::put($key, $attempts, now()->addMinutes($durationMinutes + 5));
    }

    /**
     * Check if the user should be locked out based on attempts.
     */
    protected function shouldLockout(Request $request): bool {
        if (!config('tyro-login.lockout.enabled', true)) {
            return false;
        }

        $attempts = Cache::get($this->lockoutAttemptsKey($request), 0);
        $maxAttempts = config('tyro-login.lockout.max_attempts', 5);

        return $attempts >= $maxAttempts;
    }

    /**
     * Get the remaining attempts before lockout.
     */
    protected function getRemainingAttempts(Request $request): int {
        $attempts = Cache::get($this->lockoutAttemptsKey($request), 0);
        $maxAttempts = config('tyro-login.lockout.max_attempts', 5);

        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Lock out the user.
     */
    protected function lockoutUser(Request $request): void {
        $durationMinutes = (int) config('tyro-login.lockout.duration_minutes', 15);
        $releaseTime = now()->addMinutes($durationMinutes)->timestamp;

        Cache::put($this->lockoutKey($request), $releaseTime, now()->addMinutes($durationMinutes));
    }

    /**
     * Clear the lockout for the user.
     */
    protected function clearLockout(Request $request): void {
        Cache::forget($this->lockoutKey($request));
        Cache::forget($this->lockoutAttemptsKey($request));
    }

    /**
     * Handle magic link login.
     */
    public function magicLogin(Request $request): RedirectResponse {
        $hash = $request->input('hash');

        if (!$hash) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['login' => 'Invalid magic link.']);
        }

        $data = Cache::get("tyro_magic_link_{$hash}");

        if (!$data) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['login' => 'Invalid or expired magic link.']);
        }

        if ($data['used']) {
             return redirect()->route('tyro-login.login')
                ->withErrors(['login' => 'This magic link has already been used.']);
        }

        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        $user = $userModel::find($data['user_id']);

        if (!$user) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['login' => 'User associated with this magic link not found.']);
        }

        // Mark as used
        $data['used'] = true;
        $data['ip'] = $request->ip();
        
        $expiresAt = Carbon::createFromTimestamp($data['expires_at']);
        Cache::put("tyro_magic_link_{$hash}", $data, $expiresAt);

        // Regenerate session for security and to ensure a clean state
        $request->session()->regenerate();
        
        Auth::login($user);
        
        if (config('tyro-login.debug', false)) {
            Log::info('Tyro Login - Magic Link Login Successful', [
                'user_id' => $user->id,
            ]);
        }

        return redirect()->intended(config('tyro-login.redirects.after_login', '/'));
    }
}
