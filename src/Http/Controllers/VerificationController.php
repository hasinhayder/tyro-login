<?php

namespace HasinHayder\TyroLogin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VerificationController extends Controller
{
    /**
     * Show the email verification notice.
     */
    public function showVerificationNotice(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('tyro-login.verification.email');

        if (!$email) {
            return redirect()->route('tyro-login.login');
        }

        return view('tyro-login::verify-email', [
            'layout' => config('tyro-login.layout', 'centered'),
            'branding' => config('tyro-login.branding'),
            'backgroundImage' => config('tyro-login.background_image'),
            'email' => $email,
            'pageContent' => config('tyro-login.pages.verify_email', [
                'title' => 'Verify Your Email',
                'subtitle' => 'We\'ve sent a verification link to your email address.',
            ]),
        ]);
    }

    /**
     * Generate a verification URL for the user.
     */
    public static function generateVerificationUrl($user): string
    {
        $token = Str::random(64);
        $expiresAt = now()->addMinutes(config('tyro-login.verification.expire', 60));

        // Store token in cache
        Cache::put(
            "tyro-login:email-verify:{$token}",
            [
                'user_id' => $user->id,
                'email' => $user->email,
            ],
            $expiresAt
        );

        $url = URL::temporarySignedRoute(
            'tyro-login.verification.verify',
            $expiresAt,
            ['token' => $token]
        );

        // Log the verification URL for development
        Log::info('Tyro Login - Email Verification URL', [
            'email' => $user->email,
            'url' => $url,
        ]);

        // Also dump to error log for easy access during development
        error_log("Tyro Login - Verification URL for {$user->email}: {$url}");

        return $url;
    }

    /**
     * Verify the email address.
     */
    public function verify(Request $request, string $token): RedirectResponse
    {
        // Validate signature
        if (!$request->hasValidSignature()) {
            return redirect()->route('tyro-login.login')
                ->with('error', 'The verification link is invalid or has expired.');
        }

        // Get user data from cache
        $data = Cache::get("tyro-login:email-verify:{$token}");

        if (!$data) {
            return redirect()->route('tyro-login.login')
                ->with('error', 'The verification link is invalid or has expired.');
        }

        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        $user = $userModel::find($data['user_id']);

        if (!$user) {
            return redirect()->route('tyro-login.login')
                ->with('error', 'User not found.');
        }

        // Mark email as verified
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // Clear the verification token
        Cache::forget("tyro-login:email-verify:{$token}");

        // Clear the session email
        $request->session()->forget('tyro-login.verification.email');

        // Log the user in
        Auth::login($user);

        return redirect(config('tyro-login.redirects.after_register', '/'))
            ->with('success', 'Your email has been verified successfully.');
    }

    /**
     * Resend the verification email.
     */
    public function resend(Request $request): RedirectResponse
    {
        $email = $request->session()->get('tyro-login.verification.email');

        if (!$email) {
            return redirect()->route('tyro-login.login');
        }

        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        $user = $userModel::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('tyro-login.login');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('tyro-login.login');
        }

        // Generate new verification URL
        $verificationUrl = self::generateVerificationUrl($user);

        // In a real application, you would send this via email
        // For development, it's logged above

        return redirect()->route('tyro-login.verification.notice')
            ->with('success', 'A new verification link has been sent to your email address.');
    }
}
