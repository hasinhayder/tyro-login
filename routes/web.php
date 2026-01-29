<?php

use HasinHayder\TyroLogin\Http\Controllers\LoginController;
use HasinHayder\TyroLogin\Http\Controllers\PasswordResetController;
use HasinHayder\TyroLogin\Http\Controllers\RegisterController;
use HasinHayder\TyroLogin\Http\Controllers\SocialAuthController;
use HasinHayder\TyroLogin\Http\Controllers\TwoFactorController;
use HasinHayder\TyroLogin\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tyro Login Routes
|--------------------------------------------------------------------------
|
| These routes handle authentication for the Tyro Login package.
|
*/

// Guest routes
Route::middleware('guest')->group(function () {
    // Magic Link
    Route::get('mlogin', [LoginController::class, 'magicLogin'])->name('magic-link');

    // Login routes
    Route::get(config('tyro-login.routes.login', 'login'), [LoginController::class, 'showLoginForm'])
        ->name('login');

    Route::post(config('tyro-login.routes.login', 'login'), [LoginController::class, 'login'])
        ->name('login.submit');

    // Magic link request route
    Route::post('magic-link/request', [LoginController::class, 'requestMagicLink'])
        ->name('magic-link.request');

    // Lockout route
    Route::get('lockout', [LoginController::class, 'showLockout'])
        ->name('lockout');

    // Registration routes
    if (config('tyro-login.registration.enabled', true)) {
        Route::get(config('tyro-login.routes.register', 'register'), [RegisterController::class, 'showRegistrationForm'])
            ->name('register');

        Route::post(config('tyro-login.routes.register', 'register'), [RegisterController::class, 'register'])
            ->name('register.submit');
    }

    // Email verification routes
    Route::get('email/verify', [VerificationController::class, 'showVerificationNotice'])
        ->name('verification.notice');

    Route::get('email/not-verified', [VerificationController::class, 'showEmailNotVerified'])
        ->name('verification.not-verified');

    Route::get('email/verify/{token}', [VerificationController::class, 'verify'])
        ->name('verification.verify');

    Route::post('email/resend', [VerificationController::class, 'resend'])
        ->name('verification.resend');

    // Password reset routes
    Route::get('forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->name('password.email');

    Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');

    Route::post('reset-password', [PasswordResetController::class, 'reset'])
        ->name('password.update');

    // OTP verification routes (for login with OTP enabled)
    Route::get('otp/verify', [LoginController::class, 'showOtpForm'])
        ->name('otp.verify');

    Route::post('otp/verify', [LoginController::class, 'verifyOtp'])
        ->name('otp.submit');

    Route::post('otp/resend', [LoginController::class, 'resendOtp'])
        ->name('otp.resend');

    Route::get('otp/cancel', [LoginController::class, 'cancelOtp'])
        ->name('otp.cancel');

    // Social login routes
    Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->name('social.redirect');

    Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->name('social.callback');

    // 2FA Challenge routes (guest because user is not fully logged in yet)
    Route::get('two-factor/challenge', [TwoFactorController::class, 'showChallenge'])
        ->name('two-factor.challenge');
        
    Route::post('two-factor/verify', [TwoFactorController::class, 'verify'])
        ->name('two-factor.verify');
        
    // 2FA Setup routes (guest because user is not fully logged in yet)
    Route::get('two-factor/setup', [TwoFactorController::class, 'showSetup'])
        ->name('two-factor.setup');
        
    Route::post('two-factor/confirm', [TwoFactorController::class, 'confirm'])
        ->name('two-factor.confirm');
        
    Route::post('two-factor/skip', [TwoFactorController::class, 'skip'])
        ->name('two-factor.skip');
        
    Route::get('two-factor/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes'])
        ->name('two-factor.recovery-codes');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Logout - POST only for CSRF protection
    Route::post(config('tyro-login.routes.logout', 'logout'), [LoginController::class, 'logout'])
        ->name('logout');
    Route::get('two-factor/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes'])
        ->name('two-factor.recovery-codes');
});