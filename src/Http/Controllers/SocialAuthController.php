<?php

namespace HasinHayder\TyroLogin\Http\Controllers;

use HasinHayder\TyroLogin\Mail\WelcomeMail;
use HasinHayder\TyroLogin\Models\SocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller {
    /**
     * Supported OAuth providers.
     */
    protected array $supportedProviders = [
        'google',
        'facebook',
        'github',
        'twitter',
        'linkedin',
        'bitbucket',
        'gitlab',
        'slack',
    ];

    /**
     * Redirect the user to the OAuth provider.
     */
    public function redirect(Request $request, string $provider): RedirectResponse {
        // Check if social login is enabled
        if (! $this->isSocialLoginEnabled()) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => 'Social login is not enabled.']);
        }

        // Validate provider
        if (! $this->isProviderEnabled($provider)) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => 'This social login provider is not available.']);
        }

        // Store the intended action (login or register) in session
        $action = $this->normalizeAction($request->query('action', 'login'));
        $request->session()->put('tyro-login.social.action', $action);

        try {
            // Use the correct driver name for providers with different naming
            $driverName = match ($provider) {
                'linkedin' => 'linkedin-openid',
                'slack' => 'slack-openid',
                default => $provider,
            };

            return Socialite::driver($driverName)->redirect();
        } catch (\Exception $e) {
            Log::error('Social login redirect failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => config('tyro-login.social.messages.provider_error', 'An error occurred with the social login provider.')]);
        }
    }

    /**
     * Handle the callback from the OAuth provider.
     */
    public function callback(Request $request, string $provider): RedirectResponse {
        // Check if social login is enabled
        if (! $this->isSocialLoginEnabled()) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => 'Social login is not enabled.']);
        }

        // Validate provider
        if (! $this->isProviderEnabled($provider)) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => 'This social login provider is not available.']);
        }

        try {
            // Use the correct driver name for providers with different naming
            $driverName = match ($provider) {
                'linkedin' => 'linkedin-openid',
                'slack' => 'slack-openid',
                default => $provider,
            };

            $socialUser = Socialite::driver($driverName)->user();
        } catch (\Exception $e) {
            Log::error('Social login callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => config('tyro-login.social.messages.provider_error', 'An error occurred with the social login provider.')]);
        }

        // Get the intended action
        $action = $this->normalizeAction($request->session()->pull('tyro-login.social.action', 'login'));

        // Process the social login
        return $this->handleSocialUser($request, $socialUser, $provider, $action);
    }

    /**
     * Handle the social user authentication.
     */
    protected function handleSocialUser(Request $request, SocialiteUser $socialUser, string $provider, string $action): RedirectResponse {
        $email = $socialUser->getEmail();

        // Check if email is required and available
        if (empty($email)) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => config('tyro-login.social.messages.email_required', 'Email address is required for social login.')]);
        }

        // Debug logging
        if (config('tyro-login.debug', false)) {
            Log::info('Tyro Login - Social Authentication', [
                'provider' => $provider,
                'provider_user_id' => $socialUser->getId(),
                'email' => Str::mask($email, '*', 3),
                'name' => $socialUser->getName(),
                'action' => $action,
            ]);
        }

        // Check if we have an existing social account for this provider
        $socialAccount = SocialAccount::findByProvider($provider, $socialUser->getId());

        if ($socialAccount) {
            // Update token information
            $this->updateSocialAccount($socialAccount, $socialUser);

            // Mark email as verified (social login confirms email ownership)
            $this->markEmailAsVerified($socialAccount->user);

            // Log the user in
            Auth::login($socialAccount->user);
            $request->session()->regenerate();

            return $this->handlePostLoginRedirect($request, $socialAccount->user);
        }

        // Check if user exists with this email
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        $user = $userModel::where('email', $email)->first();

        if ($user) {
            // Link social account to existing user if enabled
            if (config('tyro-login.social.link_existing_accounts', true)) {
                $this->createSocialAccount($user, $socialUser, $provider);

                // Mark email as verified (social login confirms email ownership)
                $this->markEmailAsVerified($user);

                Auth::login($user);
                $request->session()->regenerate();

                return $this->handlePostLoginRedirect($request, $user);
            }

            // User exists but linking is disabled
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => 'An account with this email already exists. Please login with your password.']);
        }

        // No user exists - check if auto-registration is enabled
        if (! $this->shouldCreateMissingUser($action)) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => config('tyro-login.social.messages.account_not_found', 'No account found with this email. Please register first.')]);
        }

        // Check if registration is enabled
        if (! config('tyro-login.registration.enabled', true)) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => 'Registration is currently disabled.']);
        }

        // Create new user
        $user = $this->createUser($socialUser);

        // Create social account link
        $this->createSocialAccount($user, $socialUser, $provider);

        // Assign Tyro role if package is installed
        $this->assignTyroRole($user);

        // Send welcome email if enabled
        if (config('tyro-login.emails.welcome.enabled', true)) {
            Mail::to($user->email)->send(new WelcomeMail(
                userName: $user->name ?? 'User',
                loginUrl: url(config('tyro-login.routes.prefix', '').'/login')
            ));
        }

        // Log the user in
        Auth::login($user);
        $request->session()->regenerate();

        return $this->handlePostLoginRedirect($request, $user, config('tyro-login.redirects.after_register', '/'));
    }

    /**
     * Handle post-login redirect with 2FA checks.
     */
    protected function handlePostLoginRedirect(Request $request, $user, string $fallbackRedirect = null): RedirectResponse {
        $fallbackRedirect = $fallbackRedirect ?? config('tyro-login.redirects.after_login', '/');

        if (config('tyro-login.two_factor.enabled', false)) {
            if (filled($user->two_factor_confirmed_at)) {
                Auth::logout();
                $request->session()->put('login.id', $user->id);
                $request->session()->put('login.remember', false);

                return redirect()->route('tyro-login.two-factor.challenge');
            } else {
                if (config('tyro-login.two_factor.allow_skip', false)) {
                    $forcedRoles = config('tyro-login.two_factor.forced_roles', '');
                    $roles = $forcedRoles ? array_filter(array_map('trim', explode(',', $forcedRoles))) : [];
                    $isForced = false;

                    if (! empty($roles)) {
                        if (method_exists($user, 'hasRole')) {
                            foreach ($roles as $role) {
                                if ($user->hasRole($role)) {
                                    $isForced = true;
                                    break;
                                }
                            }
                        } elseif (isset($user->role)) {
                            $isForced = in_array($user->role, $roles);
                        }
                    }

                    if (! $isForced) {
                        $ignoreCookieName = 'tyro_2fa_ignore_'.$user->id;
                        if ($request->cookie($ignoreCookieName)) {
                            return redirect()->intended($fallbackRedirect);
                        }
                    }
                }

                Auth::logout();
                $request->session()->put('login.id', $user->id);
                $request->session()->put('login.remember', false);

                return redirect()->route('tyro-login.two-factor.setup');
            }
        }

        return redirect()->intended($fallbackRedirect);
    }

    /**
     * Create a new user from social data.
     */
    protected function createUser(SocialiteUser $socialUser): mixed {
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');

        $user = new $userModel;
        $user->forceFill([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'password' => Hash::make(Str::random(32)), // Random password for social users
            'email_verified_at' => now(), // Social emails are considered verified
        ]);

        $user->save();

        return $user;
    }

    /**
     * Create a social account for a user.
     */
    protected function createSocialAccount($user, SocialiteUser $socialUser, string $provider): SocialAccount {
        return SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_user_id' => $socialUser->getId(),
            'provider_email' => $socialUser->getEmail(),
            'provider_avatar' => $socialUser->getAvatar(),
            'access_token' => $socialUser->token ?? null,
            'refresh_token' => $socialUser->refreshToken ?? null,
            'token_expires_at' => isset($socialUser->expiresIn)
                ? now()->addSeconds($socialUser->expiresIn)
                : null,
        ]);
    }

    /**
     * Update an existing social account with new token data.
     */
    protected function updateSocialAccount(SocialAccount $socialAccount, SocialiteUser $socialUser): void {
        $socialAccount->update([
            'provider_email' => $socialUser->getEmail(),
            'provider_avatar' => $socialUser->getAvatar(),
            'access_token' => $socialUser->token ?? null,
            'refresh_token' => $socialUser->refreshToken ?? null,
            'token_expires_at' => isset($socialUser->expiresIn)
                ? now()->addSeconds($socialUser->expiresIn)
                : null,
        ]);
    }

    /**
     * Check if social login is enabled globally.
     */
    protected function isSocialLoginEnabled(): bool {
        return config('tyro-login.social.enabled', false);
    }

    /**
     * Check if a specific provider is enabled.
     */
    protected function isProviderEnabled(string $provider): bool {
        if (! in_array($provider, $this->supportedProviders)) {
            return false;
        }

        return config("tyro-login.social.providers.{$provider}.enabled", false);
    }

    /**
     * Normalize the intended social auth action.
     */
    protected function normalizeAction(string $action): string {
        return in_array($action, ['login', 'register'], true) ? $action : 'login';
    }

    /**
     * Determine whether a missing social user should be created.
     */
    protected function shouldCreateMissingUser(string $action): bool {
        if (in_array($this->normalizeAction($action), ['login', 'register'], true)) {
            return true;
        }

        return config('tyro-login.social.auto_register', true);
    }

    /**
     * Assign the default Tyro role to a user if Tyro is installed.
     */
    protected function assignTyroRole($user): void {
        if (! config('tyro-login.tyro.assign_default_role', true)) {
            return;
        }

        // Check if Tyro is installed
        if (! class_exists('HasinHayder\\Tyro\\Models\\Role')) {
            return;
        }

        // Check if user has the HasTyroRoles trait
        if (! method_exists($user, 'assignRole')) {
            return;
        }

        $roleSlug = config('tyro-login.tyro.default_role_slug', 'user');

        try {
            $roleModel = 'HasinHayder\\Tyro\\Models\\Role';
            $role = $roleModel::where('slug', $roleSlug)->first();

            if ($role) {
                $user->assignRole($role);
            }
        } catch (\Exception $e) {
            // Silently fail if role assignment fails
            report($e);
        }
    }

    /**
     * Mark user's email as verified if not already verified.
     * Social login confirms email ownership through the OAuth provider.
     */
    protected function markEmailAsVerified($user): void {
        if (! config('tyro-login.social.auto_verify_email', true)) {
            return;
        }

        // Only update if email is not already verified
        if (empty($user->email_verified_at)) {
            $user->email_verified_at = now();
            $user->save();

            if (config('tyro-login.debug', false)) {
                Log::info('Tyro Login - Email Verified via Social Login', [
                    'user_id' => $user->id,
                    'email' => Str::mask($user->email, '*', 3),
                ]);
            }
        }
    }

    /**
     * Get enabled providers for display.
     */
    public static function getEnabledProviders(): array {
        if (! config('tyro-login.social.enabled', false)) {
            return [];
        }

        $providers = [];
        $configProviders = config('tyro-login.social.providers', []);

        foreach ($configProviders as $key => $provider) {
            if ($provider['enabled'] ?? false) {
                $providers[$key] = $provider;
            }
        }

        return $providers;
    }
}
