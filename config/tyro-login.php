<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tyro Login Version
    |--------------------------------------------------------------------------
    */
    'version' => '1.1.0',

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, debug information such as OTP codes, verification tokens,
    | and password reset tokens will be logged to storage/logs/laravel.log.
    | This should be disabled in production environments.
    |
    */
    'debug' => env('TYRO_LOGIN_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Layout Style
    |--------------------------------------------------------------------------
    |
    | Choose the layout style for the authentication pages.
    | Options: 'centered', 'split-left', 'split-right'
    |
    | - centered: Form in the center of the page
    | - split-left: Two-column layout with background image on left, form on right
    | - split-right: Two-column layout with form on left, background image on right
    |
    */
    'layout' => env('TYRO_LOGIN_LAYOUT', 'centered'),

    /*
    |--------------------------------------------------------------------------
    | Background Image
    |--------------------------------------------------------------------------
    |
    | The background image URL for split layouts.
    | This can be a local path or an external URL.
    |
    */
    'background_image' => env('TYRO_LOGIN_BACKGROUND_IMAGE', 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1920&q=80'),

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    */
    'branding' => [
        'app_name' => env('TYRO_LOGIN_APP_NAME', env('APP_NAME', 'Laravel')),
        'logo' => env('TYRO_LOGIN_LOGO', null),
        'logo_height' => env('TYRO_LOGIN_LOGO_HEIGHT', '48px'),
        'primary_color' => env('TYRO_LOGIN_PRIMARY_COLOR', '#4f46e5'),
        'primary_hover_color' => env('TYRO_LOGIN_PRIMARY_HOVER_COLOR', '#4338ca'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Settings
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => env('TYRO_LOGIN_ROUTE_PREFIX', ''),
        'middleware' => ['web'],
        'login' => 'login',
        'logout' => 'logout',
        'register' => 'register',
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    |
    | Configure where users should be redirected after various actions.
    |
    */
    'redirects' => [
        'after_login' => env('TYRO_LOGIN_REDIRECT_AFTER_LOGIN', '/'),
        'after_logout' => env('TYRO_LOGIN_REDIRECT_AFTER_LOGOUT', '/login'),
        'after_register' => env('TYRO_LOGIN_REDIRECT_AFTER_REGISTER', '/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Settings
    |--------------------------------------------------------------------------
    */
    'registration' => [
        // Whether registration is enabled
        'enabled' => env('TYRO_LOGIN_REGISTRATION_ENABLED', true),

        // Whether to automatically log in the user after registration
        'auto_login' => env('TYRO_LOGIN_REGISTRATION_AUTO_LOGIN', true),

        // Whether to require email verification after registration
        'require_email_verification' => env('TYRO_LOGIN_REQUIRE_EMAIL_VERIFICATION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tyro Integration
    |--------------------------------------------------------------------------
    |
    | If hasinhayder/tyro is installed, these settings control the integration.
    |
    */
    'tyro' => [
        // Whether to assign a default role to new users
        'assign_default_role' => env('TYRO_LOGIN_ASSIGN_DEFAULT_ROLE', true),

        // The default role slug to assign to new users
        'default_role_slug' => env('TYRO_LOGIN_DEFAULT_ROLE_SLUG', 'user'),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    */
    'user_model' => env('TYRO_LOGIN_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */
    'features' => [
        // Show "Remember Me" checkbox on login form
        'remember_me' => env('TYRO_LOGIN_REMEMBER_ME', true),

        // Show "Forgot Password" link on login form
        'forgot_password' => env('TYRO_LOGIN_FORGOT_PASSWORD', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Rules
    |--------------------------------------------------------------------------
    |
    | Minimum password requirements for registration.
    |
    */
    'password' => [
        'min_length' => env('TYRO_LOGIN_PASSWORD_MIN_LENGTH', 8),
        'require_confirmation' => env('TYRO_LOGIN_PASSWORD_REQUIRE_CONFIRMATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Field
    |--------------------------------------------------------------------------
    |
    | The field used for login. Options: 'email', 'username', 'both'
    |
    */
    'login_field' => env('TYRO_LOGIN_FIELD', 'email'),

    /*
    |--------------------------------------------------------------------------
    | Page Content
    |--------------------------------------------------------------------------
    |
    | Configure the content displayed on different pages.
    |
    */
    'pages' => [
        'login' => [
            'background_title' => env('TYRO_LOGIN_BG_TITLE', 'Welcome Back!'),
            'background_description' => env('TYRO_LOGIN_BG_DESCRIPTION', 'Sign in to access your account and continue where you left off. We\'re glad to see you again.'),
        ],
        'register' => [
            'background_title' => env('TYRO_LOGIN_REGISTER_BG_TITLE', 'Join Us Today!'),
            'background_description' => env('TYRO_LOGIN_REGISTER_BG_DESCRIPTION', 'Create your account and start your journey with us. It only takes a minute to get started.'),
        ],
        'verify_email' => [
            'title' => env('TYRO_LOGIN_VERIFY_EMAIL_TITLE', 'Verify Your Email'),
            'subtitle' => env('TYRO_LOGIN_VERIFY_EMAIL_SUBTITLE', 'We\'ve sent a verification link to your email address.'),
            'background_title' => env('TYRO_LOGIN_VERIFY_EMAIL_BG_TITLE', 'Check Your Email'),
            'background_description' => env('TYRO_LOGIN_VERIFY_EMAIL_BG_DESCRIPTION', 'We\'ve sent a verification link to your email address. Click the link to verify your account.'),
        ],
        'forgot_password' => [
            'title' => env('TYRO_LOGIN_FORGOT_PASSWORD_TITLE', 'Forgot Password?'),
            'subtitle' => env('TYRO_LOGIN_FORGOT_PASSWORD_SUBTITLE', 'Enter your email and we\'ll send you a reset link.'),
            'background_title' => env('TYRO_LOGIN_FORGOT_PASSWORD_BG_TITLE', 'Forgot Your Password?'),
            'background_description' => env('TYRO_LOGIN_FORGOT_PASSWORD_BG_DESCRIPTION', 'No worries! Enter your email and we\'ll send you a link to reset your password.'),
        ],
        'reset_password' => [
            'title' => env('TYRO_LOGIN_RESET_PASSWORD_TITLE', 'Reset Password'),
            'subtitle' => env('TYRO_LOGIN_RESET_PASSWORD_SUBTITLE', 'Enter your new password below.'),
            'background_title' => env('TYRO_LOGIN_RESET_PASSWORD_BG_TITLE', 'Reset Your Password'),
            'background_description' => env('TYRO_LOGIN_RESET_PASSWORD_BG_DESCRIPTION', 'Create a new secure password for your account.'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Verification Settings
    |--------------------------------------------------------------------------
    |
    | Configure email verification token expiration time.
    |
    */
    'verification' => [
        // Token expiration time in minutes
        'expire' => env('TYRO_LOGIN_VERIFICATION_EXPIRE', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Settings
    |--------------------------------------------------------------------------
    |
    | Configure password reset token expiration time.
    |
    */
    'password_reset' => [
        // Token expiration time in minutes
        'expire' => env('TYRO_LOGIN_PASSWORD_RESET_EXPIRE', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Math Captcha Settings
    |--------------------------------------------------------------------------
    |
    | When enabled, a simple math captcha (addition/subtraction) will be shown
    | on the login and/or registration forms. This helps prevent automated
    | submissions without requiring external services.
    |
    */
    'captcha' => [
        // Whether captcha is enabled on login form
        'enabled_login' => env('TYRO_LOGIN_CAPTCHA_LOGIN', false),

        // Whether captcha is enabled on registration form
        'enabled_register' => env('TYRO_LOGIN_CAPTCHA_REGISTER', false),

        // Captcha label text
        'label' => env('TYRO_LOGIN_CAPTCHA_LABEL', 'Security Check'),

        // Captcha placeholder text
        'placeholder' => env('TYRO_LOGIN_CAPTCHA_PLACEHOLDER', 'Enter the answer'),

        // Error message when captcha is incorrect
        'error_message' => env('TYRO_LOGIN_CAPTCHA_ERROR', 'Incorrect answer. Please try again.'),

        // Minimum number for math operation
        'min_number' => env('TYRO_LOGIN_CAPTCHA_MIN', 1),

        // Maximum number for math operation
        'max_number' => env('TYRO_LOGIN_CAPTCHA_MAX', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Login OTP Settings
    |--------------------------------------------------------------------------
    |
    | When enabled, users will receive a one-time password via email after
    | entering valid credentials. They must enter the OTP to complete login.
    | The OTP is stored in cache (no database required).
    |
    */
    'otp' => [
        // Whether OTP verification is enabled for login
        'enabled' => env('TYRO_LOGIN_OTP_ENABLED', false),

        // Number of digits in the OTP (4-8)
        'length' => env('TYRO_LOGIN_OTP_LENGTH', 4),

        // OTP expiration time in minutes
        'expire' => env('TYRO_LOGIN_OTP_EXPIRE', 5),

        // Maximum OTP resend attempts
        'max_resend' => env('TYRO_LOGIN_OTP_MAX_RESEND', 3),

        // Cooldown between resends in seconds
        'resend_cooldown' => env('TYRO_LOGIN_OTP_RESEND_COOLDOWN', 60),

        // Page title
        'title' => env('TYRO_LOGIN_OTP_TITLE', 'Enter Verification Code'),

        // Page subtitle (supports :email placeholder)
        'subtitle' => env('TYRO_LOGIN_OTP_SUBTITLE', 'We\'ve sent a :length-digit code to :email'),

        // Input label
        'label' => env('TYRO_LOGIN_OTP_LABEL', 'Verification Code'),

        // Input placeholder
        'placeholder' => env('TYRO_LOGIN_OTP_PLACEHOLDER', 'Enter code'),

        // Submit button text
        'submit_button' => env('TYRO_LOGIN_OTP_SUBMIT_BUTTON', 'Verify'),

        // Resend button text
        'resend_button' => env('TYRO_LOGIN_OTP_RESEND_BUTTON', 'Resend Code'),

        // Error message when OTP is incorrect
        'error_message' => env('TYRO_LOGIN_OTP_ERROR', 'Invalid or expired verification code.'),

        // Success message when OTP is resent
        'resend_success' => env('TYRO_LOGIN_OTP_RESEND_SUCCESS', 'A new verification code has been sent to your email.'),

        // Error message when max resends reached
        'max_resend_error' => env('TYRO_LOGIN_OTP_MAX_RESEND_ERROR', 'Maximum resend attempts reached. Please try logging in again.'),

        // Background title (for split layouts)
        'background_title' => env('TYRO_LOGIN_OTP_BG_TITLE', 'Almost There!'),

        // Background description (for split layouts)
        'background_description' => env('TYRO_LOGIN_OTP_BG_DESCRIPTION', 'Enter the verification code we sent to your email to complete the login process.'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lockout Settings
    |--------------------------------------------------------------------------
    |
    | When enabled, users will be locked out after too many failed login
    | attempts. The lockout state is stored in cache (no database required).
    | After the lockout duration expires, the user can try again and the
    | cache will be automatically cleared.
    |
    */
    'lockout' => [
        // Whether lockout feature is enabled
        'enabled' => env('TYRO_LOGIN_LOCKOUT_ENABLED', true),

        // Number of failed attempts before lockout
        'max_attempts' => env('TYRO_LOGIN_LOCKOUT_MAX_ATTEMPTS', 3),

        // Lockout duration in minutes
        'duration_minutes' => env('TYRO_LOGIN_LOCKOUT_DURATION', 2),

        // Show remaining attempts after failed login
        'show_attempts_left' => env('TYRO_LOGIN_SHOW_ATTEMPTS_LEFT', false),

        // Auto-redirect to login page when countdown expires
        'auto_redirect' => env('TYRO_LOGIN_LOCKOUT_AUTO_REDIRECT', true),

        // Message shown on lockout page (supports :minutes placeholder)
        'message' => env('TYRO_LOGIN_LOCKOUT_MESSAGE', 'Too many failed login attempts. Please try again in :minutes minutes.'),

        // Lockout page title
        'title' => env('TYRO_LOGIN_LOCKOUT_TITLE', 'Account Temporarily Locked'),

        // Lockout page subtitle
        'subtitle' => env('TYRO_LOGIN_LOCKOUT_SUBTITLE', 'For your security, we\'ve temporarily locked your account.'),
    ],
];
