<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tyro Login Version
    |--------------------------------------------------------------------------
    */
    'version' => '1.0.0',

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
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'enabled' => env('TYRO_LOGIN_RATE_LIMITING', true),
        'max_attempts' => env('TYRO_LOGIN_MAX_ATTEMPTS', 5),
        'decay_minutes' => env('TYRO_LOGIN_DECAY_MINUTES', 1),
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

        // Message shown on lockout page (supports :minutes placeholder)
        'message' => env('TYRO_LOGIN_LOCKOUT_MESSAGE', 'Too many failed login attempts. Please try again in :minutes minutes.'),

        // Lockout page title
        'title' => env('TYRO_LOGIN_LOCKOUT_TITLE', 'Account Temporarily Locked'),

        // Lockout page subtitle
        'subtitle' => env('TYRO_LOGIN_LOCKOUT_SUBTITLE', 'For your security, we\'ve temporarily locked your account.'),
    ],
];
