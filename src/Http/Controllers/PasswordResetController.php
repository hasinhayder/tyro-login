<?php

namespace HasinHayder\TyroLogin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Show the forgot password form.
     */
    public function showForgotPasswordForm(): View
    {
        return view('tyro-login::forgot-password', [
            'layout' => config('tyro-login.layout', 'centered'),
            'branding' => config('tyro-login.branding'),
            'backgroundImage' => config('tyro-login.background_image'),
            'loginField' => config('tyro-login.login_field', 'email'),
            'pageContent' => config('tyro-login.pages.forgot_password', [
                'title' => 'Forgot Password?',
                'subtitle' => 'Enter your email and we\'ll send you a reset link.',
            ]),
        ]);
    }

    /**
     * Send password reset link.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $loginField = config('tyro-login.login_field', 'email');

        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        $user = $userModel::where('email', $request->email)->first();

        if (!$user) {
            // Don't reveal that user doesn't exist for security
            return redirect()->route('tyro-login.password.request')
                ->with('success', 'If an account with that email exists, we\'ve sent a password reset link.');
        }

        // Generate password reset URL
        $resetUrl = $this->generateResetUrl($user);

        // In a real application, you would send this via email
        // For development, it's logged above

        return redirect()->route('tyro-login.password.request')
            ->with('success', 'If an account with that email exists, we\'ve sent a password reset link.');
    }

    /**
     * Generate a password reset URL for the user.
     */
    public function generateResetUrl($user): string
    {
        $token = Str::random(64);
        $expiresAt = now()->addMinutes(config('tyro-login.password_reset.expire', 60));

        // Store token in cache
        Cache::put(
            "tyro-login:password-reset:{$token}",
            [
                'user_id' => $user->id,
                'email' => $user->email,
            ],
            $expiresAt
        );

        $url = URL::temporarySignedRoute(
            'tyro-login.password.reset',
            $expiresAt,
            ['token' => $token]
        );

        // Log the reset URL for development
        Log::info('Tyro Login - Password Reset URL', [
            'email' => $user->email,
            'url' => $url,
        ]);

        // Also dump to error log for easy access during development
        error_log("Tyro Login - Password Reset URL for {$user->email}: {$url}");

        return $url;
    }

    /**
     * Show the password reset form.
     */
    public function showResetForm(Request $request, string $token): View|RedirectResponse
    {
        // Validate signature
        if (!$request->hasValidSignature()) {
            return redirect()->route('tyro-login.password.request')
                ->with('error', 'The password reset link is invalid or has expired.');
        }

        // Get user data from cache
        $data = Cache::get("tyro-login:password-reset:{$token}");

        if (!$data) {
            return redirect()->route('tyro-login.password.request')
                ->with('error', 'The password reset link is invalid or has expired.');
        }

        return view('tyro-login::reset-password', [
            'layout' => config('tyro-login.layout', 'centered'),
            'branding' => config('tyro-login.branding'),
            'backgroundImage' => config('tyro-login.background_image'),
            'token' => $token,
            'email' => $data['email'],
            'pageContent' => config('tyro-login.pages.reset_password', [
                'title' => 'Reset Password',
                'subtitle' => 'Enter your new password below.',
            ]),
        ]);
    }

    /**
     * Reset the password.
     */
    public function reset(Request $request): RedirectResponse
    {
        $minLength = config('tyro-login.password.min_length', 8);

        $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min($minLength)],
        ]);

        $token = $request->token;

        // Get user data from cache
        $data = Cache::get("tyro-login:password-reset:{$token}");

        if (!$data) {
            return redirect()->route('tyro-login.password.request')
                ->with('error', 'The password reset link is invalid or has expired.');
        }

        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        $user = $userModel::find($data['user_id']);

        if (!$user) {
            return redirect()->route('tyro-login.password.request')
                ->with('error', 'User not found.');
        }

        // Update the password
        $user->password = Hash::make($request->password);
        $user->save();

        // Clear the reset token
        Cache::forget("tyro-login:password-reset:{$token}");

        // Log the user in
        Auth::login($user);

        return redirect(config('tyro-login.redirects.after_login', '/'))
            ->with('success', 'Your password has been reset successfully.');
    }
}
