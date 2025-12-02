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
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialAuthController extends Controller
{
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
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        // Check if social login is enabled
        if (!$this->isSocialLoginEnabled()) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => 'Social login is not enabled.']);
        }

        // Validate provider
        if (!$this->isProviderEnabled($provider)) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => 'This social login provider is not available.']);
        }

        // Store the intended action (login or register) in session
        $action = $request->query('action', 'login');
        $request->session()->put('tyro-login.social.action', $action);

        try {
            // Use the correct driver name for providers with different naming
            $driverName = match($provider) {
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
    public function callback(Request $request, string $provider): RedirectResponse
    {
        // Check if social login is enabled
        if (!$this->isSocialLoginEnabled()) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => 'Social login is not enabled.']);
        }

        // Validate provider
        if (!$this->isProviderEnabled($provider)) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => 'This social login provider is not available.']);
        }

        try {
            // Use the correct driver name for providers with different naming
            $driverName = match($provider) {
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
        $action = $request->session()->pull('tyro-login.social.action', 'login');

        // Process the social login
        return $this->handleSocialUser($request, $socialUser, $provider, $action);
    }

    /**
     * Handle the social user authentication.
     */
    protected function handleSocialUser(Request $request, SocialiteUser $socialUser, string $provider, string $action): RedirectResponse
    {
        $email = $socialUser->getEmail();

        // Check if email is required and available
        if (empty($email)) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => config('tyro-login.social.messages.email_required', 'Email address is required for social login.')]);
        }

        // Debug logging
        if (config('tyro-login.debug', false)) {
            Log::info('=== TYRO LOGIN SOCIAL AUTH ===');
            Log::info("Provider: {$provider}");
            Log::info("Provider User ID: {$socialUser->getId()}");
            Log::info("Email: {$email}");
            Log::info("Name: {$socialUser->getName()}");
            Log::info("Action: {$action}");
            Log::info('==============================');
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

            return redirect()->intended(config('tyro-login.redirects.after_login', '/'));
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

                return redirect()->intended(config('tyro-login.redirects.after_login', '/'));
            }

            // User exists but linking is disabled
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => 'An account with this email already exists. Please login with your password.']);
        }

        // No user exists - check if auto-registration is enabled
        if (!config('tyro-login.social.auto_register', true)) {
            return redirect()->route('tyro-login.login')
                ->withErrors(['social' => config('tyro-login.social.messages.account_not_found', 'No account found with this email. Please register first.')]);
        }

        // Check if registration is enabled
        if (!config('tyro-login.registration.enabled', true)) {
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
                loginUrl: url(config('tyro-login.routes.prefix', '') . '/login')
            ));
        }

        // Log the user in
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(config('tyro-login.redirects.after_register', '/'));
    }

    /**
     * Create a new user from social data.
     */
    protected function createUser(SocialiteUser $socialUser): mixed
    {
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');

        return $userModel::create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'password' => Hash::make(Str::random(32)), // Random password for social users
            'email_verified_at' => now(), // Social emails are considered verified
        ]);
    }

    /**
     * Create a social account for a user.
     */
    protected function createSocialAccount($user, SocialiteUser $socialUser, string $provider): SocialAccount
    {
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
    protected function updateSocialAccount(SocialAccount $socialAccount, SocialiteUser $socialUser): void
    {
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
    protected function isSocialLoginEnabled(): bool
    {
        return config('tyro-login.social.enabled', false);
    }

    /**
     * Check if a specific provider is enabled.
     */
    protected function isProviderEnabled(string $provider): bool
    {
        if (!in_array($provider, $this->supportedProviders)) {
            return false;
        }

        return config("tyro-login.social.providers.{$provider}.enabled", false);
    }

    /**
     * Assign the default Tyro role to a user if Tyro is installed.
     */
    protected function assignTyroRole($user): void
    {
        if (!config('tyro-login.tyro.assign_default_role', true)) {
            return;
        }

        // Check if Tyro is installed
        if (!class_exists('HasinHayder\\Tyro\\Models\\Role')) {
            return;
        }

        // Check if user has the HasTyroRoles trait
        if (!method_exists($user, 'assignRole')) {
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
    protected function markEmailAsVerified($user): void
    {
        if (!config('tyro-login.social.auto_verify_email', true)) {
            return;
        }

        // Only update if email is not already verified
        if (empty($user->email_verified_at)) {
            $user->email_verified_at = now();
            $user->save();

            if (config('tyro-login.debug', false)) {
                Log::info('=== TYRO LOGIN EMAIL VERIFIED VIA SOCIAL ===');
                Log::info("User ID: {$user->id}");
                Log::info("Email: {$user->email}");
                Log::info('=============================================');
            }
        }
    }

    /**
     * Get enabled providers for display.
     */
    public static function getEnabledProviders(): array
    {
        if (!config('tyro-login.social.enabled', false)) {
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
