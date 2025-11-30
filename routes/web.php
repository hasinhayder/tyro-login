<?php

use HasinHayder\TyroLogin\Http\Controllers\LoginController;
use HasinHayder\TyroLogin\Http\Controllers\PasswordResetController;
use HasinHayder\TyroLogin\Http\Controllers\RegisterController;
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
    // Login routes
    Route::get(config('tyro-login.routes.login', 'login'), [LoginController::class, 'showLoginForm'])
        ->name('login');
    
    Route::post(config('tyro-login.routes.login', 'login'), [LoginController::class, 'login'])
        ->name('login.submit');

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
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Logout accessible via both GET and POST
    Route::match(['get', 'post'], config('tyro-login.routes.logout', 'logout'), [LoginController::class, 'logout'])
        ->name('logout');
});
